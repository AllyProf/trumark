<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\SmsLog;

class WhatsAppWebhookController extends Controller
{
    protected $whatsapp;

    public function __construct(\App\Services\WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Webhook verification for Meta
     */
    public function verify(Request $request)
    {
        $verifyToken = 'trumark_secure_webhook_token';

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === $verifyToken) {
                Log::info('WhatsApp Webhook Verified successfully.');
                return response($challenge, 200);
            }
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp messages and status updates
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        Log::info('[WA-BOT] Incoming webhook payload: ' . json_encode($data));

        try {
            if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
                $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
                $from = $message['from'];
                $type = $message['type'] ?? 'text';

                // --- Handle Interactive Button Reply ---
                if ($type === 'interactive') {
                    $interactiveType = $message['interactive']['type'] ?? '';
                    if ($interactiveType === 'button_reply') {
                        $buttonId = $message['interactive']['button_reply']['id'] ?? '';
                        $buttonTitle = $message['interactive']['button_reply']['title'] ?? '';
                        $text = $buttonId ?: $buttonTitle;
                        Log::info("[WA-BOT] Interactive button tapped from $from: \"$text\"");
                    } elseif ($interactiveType === 'list_reply') {
                        $text = $message['interactive']['list_reply']['id'] ?? '';
                        Log::info("[WA-BOT] List item selected from $from: \"$text\"");
                    } else {
                        $text = '';
                    }
                } else {
                    $text = trim($message['text']['body'] ?? '');
                }

                Log::info("[WA-BOT] Processed text from $from: \"$text\"");

                if (empty($text)) {
                    Log::info('[WA-BOT] Empty message ignored.');
                    return response('OK', 200);
                }

                // Find customer by phone, create draft lead if not exists
                $cleanPhone = preg_replace('/[^0-9]/', '', $from);
                $customer = Customer::where('phone', 'like', "%$cleanPhone%")->first();
                if (!$customer) {
                    $customer = Customer::create([
                        'name' => 'WhatsApp Lead (' . $from . ')',
                        'phone' => $from,
                        'is_draft' => true,
                        'notes' => 'Auto-created from WhatsApp first contact.'
                    ]);
                }

                // Log incoming message
                SmsLog::create([
                    'customer_id' => $customer ? $customer->id : null,
                    'phone' => $from,
                    'message' => "INCOMING: " . $text,
                    'status' => 'received',
                    'response' => json_encode($message),
                ]);

                // Check if bot is paused for this number (Human handoff active)
                $pausedKey = "wa_bot_paused_" . preg_replace('/[^0-9]/', '', $from);
                $isBotPaused = \Illuminate\Support\Facades\Cache::get($pausedKey, false);

                if ($isBotPaused) {
                    Log::info("[WA-BOT] Automated bot is PAUSED for customer $from. Human operator is chatting.");
                    return response('OK', 200);
                }

                // ROUTING: 1. State Flow -> 2. Ice Breakers -> 3. Commands -> 4. Keyword Matcher -> 5. AI Fallback
                $routedCommand = null;

                $responseMessage = $this->handleStateFlow($from, $text, $customer);
                if ($responseMessage !== null) {
                    Log::info('[WA-BOT] Handled via active state flow.');
                    $routedCommand = 'state_flow';
                } else {
                    $responseMessage = $this->handleIceBreaker($text);
                    if ($responseMessage) {
                        Log::info('[WA-BOT] Matched ICE BREAKER.');
                        $routedCommand = strtolower(trim($text));
                    } elseif (str_starts_with($text, '/')) {
                        Log::info('[WA-BOT] Routing to COMMAND handler.');
                        $routedCommand = ltrim(explode(' ', strtolower(trim($text)))[0], '/');
                        $responseMessage = $this->handleCommand($text, $from);
                    } else {
                        $matchedCommand = $this->handleKeywordMatch($text);
                        if ($matchedCommand) {
                            Log::info("[WA-BOT] Matched KEYWORD intent: $matchedCommand");
                            $routedCommand = ltrim($matchedCommand, '/');
                            $responseMessage = $this->handleCommand($matchedCommand, $from);
                        } else {
                            Log::info('[WA-BOT] No match — falling back to GEMINI AI.');
                            $responseMessage = $this->askGeminiAI($text);
                        }
                    }
                }

                Log::info('[WA-BOT] Response to send: ' . ($responseMessage ?? 'NULL'));

                // Send the main text reply
                if ($responseMessage === true) {
                    // Do nothing, message was already sent interactively
                } elseif ($responseMessage) {
                    $sendResult = $this->whatsapp->sendMessage($from, $responseMessage);
                    Log::info('[WA-BOT] Send result: ' . json_encode($sendResult));

                    // Capture Meta message ID (wamid) for delivery/read receipt tracking
                    $wamid = $sendResult['response']['messages'][0]['id'] ?? null;

                    SmsLog::create([
                        'customer_id'         => $customer ? $customer->id : null,
                        'phone'               => $from,
                        'message'             => "[BOT REPLY] " . $responseMessage,
                        'status'              => $sendResult['success'] ? 'sent' : 'failed',
                        'whatsapp_message_id' => $wamid,
                    ]);

                    // Send follow-up interactive buttons based on context
                    if ($routedCommand !== 'state_flow') {
                        $followUpButtons = $this->getFollowUpButtons($routedCommand);
                        if (!empty($followUpButtons)) {
                            sleep(1); // small delay so messages arrive in order
                            $this->whatsapp->sendInteractiveButtons(
                                $from,
                                "Chagua hatua inayofuata / Choose next step:",
                                $followUpButtons,
                                '',
                                'TRUMARK Stationery & Books 📚'
                            );
                        }
                    }
                }

            } else {
                // Handle Meta status update webhooks (sent → delivered → read)
                $statuses = $data['entry'][0]['changes'][0]['value']['statuses'] ?? [];
                if (!empty($statuses)) {
                    foreach ($statuses as $statusUpdate) {
                        $wamid    = $statusUpdate['id'] ?? null;
                        $newStatus= $statusUpdate['status'] ?? null; // 'sent','delivered','read','failed'
                        if ($wamid && $newStatus) {
                            $updated = SmsLog::where('whatsapp_message_id', $wamid)
                                ->update(['status' => $newStatus]);
                            Log::info("[WA-BOT] Status update: wamid=$wamid → $newStatus (rows=$updated)");
                        }
                    }
                } else {
                    Log::info('[WA-BOT] Non-message, non-status webhook event received.');
                }
            }

        } catch (\Throwable $e) {
            Log::error('[WA-BOT] EXCEPTION in handle(): ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
        }

        return response('OK', 200);
    }

    /**
     * Get follow-up button options based on which command was just handled
     */
    protected function getFollowUpButtons($command)
    {
        $mainMenu = [
            ['id' => '/books',     'title' => '📚 Vitabu'],
            ['id' => '/stationery','title' => '✏️ Vifaa'],
            ['id' => '/support',   'title' => '🤝 Msaada'],
        ];

        switch ($command) {
            case 'welcome':
            case 'help':
                return [
                    ['id' => '/products',  'title' => '📦 Bidhaa Zetu'],
                    ['id' => '/pricing',   'title' => '💰 Bei za Bidhaa'],
                    ['id' => '/support',   'title' => '🤝 Msaada'],
                ];

            case 'books':
            case 'school books':
            case 'vitabu vya shule':
                return [
                    ['id' => '/revision',  'title' => '📖 Past Papers'],
                    ['id' => '/order',     'title' => '🛒 Agiza Sasa'],
                    ['id' => '/pricing',   'title' => '💰 Bei'],
                ];

            case 'stationery':
            case 'stationery & office supplies':
            case 'stationery and office supplies':
            case 'office supplies':
            case 'vifaa vya ofisi':
                return [
                    ['id' => '/pricing',   'title' => '💰 Bei za Vifaa'],
                    ['id' => '/wholesale', 'title' => '📦 Bei ya Jumla'],
                    ['id' => '/order',     'title' => '🛒 Agiza Sasa'],
                ];

            case 'printing':
            case 'printing & photocopy':
            case 'uchapishaji':
                return [
                    ['id' => '/location',  'title' => '📍 Tawi Letu'],
                    ['id' => '/hours',     'title' => '⏰ Muda Wetu'],
                    ['id' => '/support',   'title' => '🤝 Wasiliana Nasi'],
                ];

            case 'delivery':
            case 'usafirishaji':
            case 'delivery information':
                return [
                    ['id' => '/order',     'title' => '🛒 Weka Oda'],
                    ['id' => '/payment',   'title' => '💳 Njia za Lipa'],
                    ['id' => '/support',   'title' => '🤝 Msaada'],
                ];

            case 'pricing':
            case 'bei za bidhaa':
            case 'price list':
                return [
                    ['id' => '/wholesale', 'title' => '📦 Bei ya Jumla'],
                    ['id' => '/quotation', 'title' => '📄 Pata Quotation'],
                    ['id' => '/order',     'title' => '🛒 Agiza Sasa'],
                ];

            case 'wholesale':
            case 'mauzo ya jumla':
            case 'bulk order':
                return [
                    ['id' => '/quotation', 'title' => '📄 Omba Quotation'],
                    ['id' => '/payment',   'title' => '💳 Njia za Lipa'],
                    ['id' => '/support',   'title' => '🤝 Ongea na Meneja'],
                ];

            case 'payment':
                return [
                    ['id' => '/order',     'title' => '🛒 Weka Oda'],
                    ['id' => '/track',     'title' => '🔍 Fuatilia Oda'],
                    ['id' => '/support',   'title' => '🤝 Msaada'],
                ];

            case 'order':
                return [
                    ['id' => '/payment',   'title' => '💳 Jinsi ya Kulipa'],
                    ['id' => '/delivery',  'title' => '🚚 Delivery Info'],
                    ['id' => '/support',   'title' => '🤝 Ongea na Mhudumu'],
                ];

            case 'location':
            case 'our locations / matawi yetu':
            case 'matawi yetu':
            case 'branches':
                return [
                    ['id' => '/hours',     'title' => '⏰ Muda wa Kazi'],
                    ['id' => '/delivery',  'title' => '🚚 Tunadelivery Pia'],
                    ['id' => '/support',   'title' => '📞 Piga Simu'],
                ];

            case 'revision':
                return [
                    ['id' => '/books',     'title' => '📚 Vitabu Zaidi'],
                    ['id' => '/order',     'title' => '🛒 Agiza Sasa'],
                    ['id' => '/support',   'title' => '🤝 Msaada'],
                ];

            case 'support':
            case 'customer support':
            case 'huduma kwa wateja':
                return [
                    ['id' => '/products',  'title' => '📦 Angalia Bidhaa'],
                    ['id' => '/location',  'title' => '📍 Tawi Letu'],
                    ['id' => '/hours',     'title' => '⏰ Muda Wetu'],
                ];

            default:
                // Always show main menu as fallback
                return $mainMenu;
        }
    }

    /**
     * Exact matches for Ice Breaker Buttons — Returns rich, detailed replies
     */
    protected function handleIceBreaker($text)
    {
        $t = strtolower(trim($text));

        // === SCHOOL BOOKS ===
        $booksReply = "📚 *VITABU VYA SHULE / SCHOOL BOOKS*\n\nTuna stoki kamili ya vitabu vya mtaala wa NECTA:\n\n• *Nursery & Pre-School*: Vitabu vya herufi, namba, kuchora na stadi za awali.\n• *Primary School (Standard 1-7)*: Vitabu vya kiada na ziada vya masomo yote.\n• *Secondary School (Form 1-4)*: Physics, Chemistry, Biology, Mathematics, Geography, History, English, Kiswahili.\n• *High School (Form 5-6)*: Vitabu vya tahasusi (Combinations) zote.\n\n👉 *Marudio (Past Papers & Reviews)*: Andika */revision* kupata past papers za mitihani ya taifa.\n👉 Andika */order* kuagiza vitabu unavyovihitaji!";

        // === STATIONERY ===
        $stationeryReply = "✏️ *VIFAA VYA OFISI NA SHULE / STATIONERY*\n\nTuna vifaa vyote vya ofisi na shule vya ubora wa juu:\n\n• *Karatasi*: A4 Reams (Double A, PaperOne), A3, Karatasi za Rangi.\n• *Madaftari*: Counter books (1, 2, 3, 4 Quire), Exercise books, Diaries.\n• *Vifaa vya Kuandika*: Kalamu za wino, penseli, markers, highlighters.\n• *Vifaa vya Ofisi*: Box files, staplers, punch machines, rulers, makasi, gundi.\n• *Mathematical Sets*: Seti za hesabu na CASIO Scientific Calculators.\n\n👉 Andika */pricing* kuona bei za bidhaa maarufu.\n👉 Andika */wholesale* kwa punguzo la jumla la 10%-20%!";

        // === DELIVERY ===
        $deliveryReply = "🚚 *HUDUMA YA USAFIRISHAJI / DELIVERY SERVICES*\n\nTunatuma vifaa na vitabu maeneo yote ya Tanzania:\n\n1. 🏙️ *Ndani ya Dar es Salaam*:\n   - Bodaboda/Bajaji inaleta mpaka ulipo (ndani ya masaa 2-4).\n   - Gharama: TZS 3,000 hadi TZS 5,000 (kulingana na umbali).\n\n2. 🚌 *Mikoani (Upcountry Delivery)*:\n   - Tunatuma kwa mabasi ya uhakika (Shabiby, Abood, Hood, BM, nk).\n   - Gharama ya usafiri: Kuanzia TZS 5,000 tu.\n\n🛒 Andika */order* sasa kuweka oda yako!\n📍 Andika */location* kuona matawi yetu.";

        // === SUPPORT ===
        $supportReply = $this->triggerHumanHandoff('');

        // === PRICING ===
        $pricingReply = "💰 *BEI ZA BIDHAA MAARUFU / PRICE LIST*\n\nHapa kuna bei za vifaa vyetu maarufu (Mauzo ya Reja reja):\n\n• 📑 *A4 Reams (Double A, PaperOne)*: TZS 11,500 - 13,000\n• 📓 *Counter Book 3 Quire*: TZS 2,500 kila moja\n• 📓 *Counter Book 4 Quire*: TZS 3,200 kila moja\n• 🖊️ *Kalamu Boksi 50 (Bic/Speedo)*: TZS 8,000 - 10,000\n• 📖 *Exercise Book A5*: TZS 300 - 800 kila moja\n• 🗂️ *Box Files*: TZS 3,500 - 5,000 kila moja\n• 🧮 *CASIO Scientific Calculator*: TZS 35,000 - 45,000\n\n⚠️ *Punguzo kubwa kwa mauzo ya jumla!* Andika */wholesale* kujua zaidi.";

        // === PRINTING ===
        $printingReply = "🖨️ *UCHAPISHAJI NA COPY / PRINTING & COPY SERVICES*\n\nTunatoa huduma bora na za haraka za uchapishaji:\n\n• *B&W Printing/Photocopy*: TZS 100 kwa ukurasa\n• *Color Printing*: Kuanzia TZS 500 kwa ukurasa\n• *Document Binding*: Spiral & Hard binding kwa ripoti/thesis\n• *Lamination*: Kulinda nyaraka zako muhimu\n• *Graphic Design*: Nembo, vipeperushi, business cards\n\n👉 Tuma nyaraka zako (PDF/Word) hapa moja kwa moja, kisha andika */support*!";

        // === WHOLESALE ===
        $wholesaleReply = "📦 *MAUZO YA JUMLA / WHOLESALE ORDERS*\n\nJe, unamiliki shule, duka la vitabu au taasisi?\n\n• *Punguzo la Bei*: Hadi *15% - 20%* kwa wanunuzi wa jumla na shule.\n• *Uwasilishaji Bure*: Oda kubwa za Dar es Salaam — tunaleta bure!\n• *Proforma Invoice*: Tunaandaa haraka kwa shule na taasisi.\n\n👉 Andika */quotation* kupata bei ya jumla rasmi.\n👉 Andika */support* kuongea na Meneja Mauzo wetu moja kwa moja!";

        // === LOCATION ===
        $locationReply = "📍 *MAHALI TULIPO / OUR LOCATIONS*\n\nKaribu ututembelee katika matawi yetu:\n\n1. 🏢 *Tawi la Ubungo*:\n   - Ubungo Plaza, Ghorofa ya Chini, karibu na kituo cha mwendo wa haraka.\n\n2. 🏢 *Tawi la Kimara*:\n   - Kimara Mwisho, mkabala na kituo kikuu cha mabasi.\n\n⏰ *Muda*: Jumatatu - Ijumaa: 8:00AM - 6:00PM | Jumamosi: 8:00AM - 5:00PM\n📞 *Simu*: 0794 467 694\n\n👉 Huwezi kufika? Andika */delivery* ili tukuletee ulipo!";

        // Map all possible button labels to the right reply
        $iceBreakers = [
            // Books
            'school books / vitabu vya shule' => $booksReply,
            'school books' => $booksReply,
            'vitabu vya shule' => $booksReply,
            'books' => $booksReply,
            'vitabu' => $booksReply,

            // Stationery
            'stationery & office supplies' => $stationeryReply,
            'stationery and office supplies' => $stationeryReply,
            'stationery' => $stationeryReply,
            'vifaa vya ofisi' => $stationeryReply,
            'office supplies' => $stationeryReply,

            // Delivery
            'delivery information / usafirishaji' => $deliveryReply,
            'delivery information' => $deliveryReply,
            'delivery' => $deliveryReply,
            'usafirishaji' => $deliveryReply,

            // Support
            'customer support / huduma kwa wateja' => $supportReply,
            'customer support' => $supportReply,
            'huduma kwa wateja' => $supportReply,
            'support' => $supportReply,
            'msaada' => $supportReply,

            // Pricing
            'pricing / bei' => $pricingReply,
            'pricing' => $pricingReply,
            'bei za bidhaa' => $pricingReply,
            'price list' => $pricingReply,

            // Printing
            'printing & photocopy' => $printingReply,
            'printing' => $printingReply,
            'photocopy' => $printingReply,
            'uchapishaji' => $printingReply,

            // Wholesale
            'wholesale orders / mauzo ya jumla' => $wholesaleReply,
            'wholesale' => $wholesaleReply,
            'mauzo ya jumla' => $wholesaleReply,
            'bulk order' => $wholesaleReply,

            // Location
            'our locations / matawi yetu' => $locationReply,
            'location' => $locationReply,
            'matawi yetu' => $locationReply,
            'branches' => $locationReply,
        ];

        foreach ($iceBreakers as $breaker => $reply) {
            if ($t === strtolower($breaker)) {
                return $reply;
            }
        }
        return null;
    }

    /**
     * Map common Swahili and English keywords to existing commands
     */
    protected function handleKeywordMatch($text)
    {
        $cleanText = strtolower(trim($text));

        // Remove common Swahili/English filler prefixes
        $cleanText = preg_replace('/^(mambo|habari|hi|hello|mambo vipi|niaje|habari za leo|naomba|nataka|nahitaji|nisaidie|tafadhali)\s+/i', '', $cleanText);

        // Map keywords to command strings
        $mappings = [
            // Welcome & Greetings
            'hello' => '/welcome',
            'hi' => '/welcome',
            'mambo' => '/welcome',
            'habari' => '/welcome',
            'niaje' => '/welcome',
            'karibu' => '/welcome',
            'hey' => '/welcome',
            'start' => '/welcome',
            'welcome' => '/welcome',

            // Order Flow
            'weka oda' => '/order',
            'kuagiza' => '/order',
            'agiza' => '/order',
            'order' => '/order',
            'oda' => '/order',
            'nunua' => '/order',

            // Feedback Flow
            'feedback' => '/feedback',
            'maoni' => '/feedback',
            'kadiria' => '/feedback',
            'rate' => '/feedback',
            'review' => '/feedback',

            // Books & Revision
            'books' => '/books',
            'kitabu' => '/books',
            'vitabu' => '/books',
            'shule' => '/books',
            'school' => '/books',
            'past papers' => '/revision',
            'pastpaper' => '/revision',
            'revision' => '/revision',
            'mitihani' => '/revision',
            'mtihani' => '/revision',
            'marudio' => '/revision',

            // Stationery
            'stationery' => '/stationery',
            'kalamu' => '/stationery',
            'daftari' => '/stationery',
            'reams' => '/stationery',
            'karatasi' => '/stationery',
            'ruler' => '/stationery',
            'pen' => '/stationery',
            'counter' => '/stationery',
            'pencils' => '/stationery',

            // Delivery
            'delivery' => '/delivery',
            'usafirishaji' => '/delivery',
            'kutuma' => '/delivery',
            'mikoani' => '/delivery',
            'tuma' => '/delivery',
            'ship' => '/delivery',

            // Location & Hours
            'location' => '/location',
            'ubungo' => '/location',
            'kimara' => '/location',
            'ofisi' => '/location',
            'duka' => '/location',
            'ramani' => '/location',
            'mlipo' => '/location',
            'ipo wapi' => '/location',
            'hours' => '/hours',
            'muda' => '/hours',
            'saa' => '/hours',
            'fungua' => '/hours',
            'siku' => '/hours',

            // Printing
            'printing' => '/printing',
            'kutoa copy' => '/printing',
            'print' => '/printing',
            'photocopy' => '/printing',
            'kutoa' => '/printing',
            'kucopy' => '/printing',
            'binding' => '/printing',
            'lamination' => '/printing',

            // Wholesale & Quotation
            'wholesale' => '/wholesale',
            'jumla' => '/wholesale',
            'punguza' => '/wholesale',
            'discount' => '/wholesale',
            'quotation' => '/quotation',
            'proforma' => '/quotation',
            'invoice' => '/quotation',
            'nukuu ya bei' => '/quotation',

            // Pricing & Catalog
            'bei' => '/pricing',
            'pricing' => '/pricing',
            'gharama' => '/pricing',
            'catalog' => '/catalog',
            'orodha' => '/catalog',
            'katalogi' => '/catalog',

            // Payments
            'payment' => '/payment',
            'lipa' => '/payment',
            'malipo' => '/payment',
            'bank' => '/payment',
            'benki' => '/payment',
            'mpesa' => '/payment',
            'tigo' => '/payment',
            'airtel' => '/payment',
            'lipa na' => '/payment',

            // Info & Support
            'help' => '/help',
            'msaada' => '/help',
            'maelekezo' => '/help',
            'menu' => '/help',
            'menu kuu' => '/help',
            'about' => '/trust',
            'kuhusu' => '/trust',
            'sifa' => '/trust',
            'support' => '/support',
            'mhudumu' => '/support',
            'ongea' => '/support',
            'help me' => '/support',
            'wasiliana' => '/support',
            'namba' => '/support',
            'simu' => '/support',
            'admin' => '/support',
        ];

        foreach ($mappings as $keyword => $command) {
            if (str_contains($cleanText, $keyword)) {
                return $command;
            }
        }

        return null;
    }

    /**
     * Route specific commands
     */
    protected function handleCommand($text, $customerPhone)
    {
        // Extract command without slash and convert to lowercase
        $parts = explode(' ', strtolower(trim($text)));
        $command = ltrim($parts[0], '/');

        switch ($command) {
            // Core
            case 'products':
                return "📦 *BIDHAA ZETU / OUR PRODUCTS*\n\nTRUMARK Stationery & Books tunauza bidhaa bora za kielimu, ofisi na shule:\n\n1. 📚 *Vitabu vya Shule (School Books)*\n   - Vitabu vya Nursery, Primary, Sekondari, A-Level na maandalizi ya NECTA.\n   - Review books na Past Papers za mitihani yote.\n\n2. ✏️ *Vifaa vya Ofisi na Shule (Stationery)*\n   - Daftari, kalamu, penseli, karatasi za printa (reams), school bags na vifaa vingi.\n\n3. 🖨️ *Huduma za Uchapishaji (Printing Services)*\n   - Printing, Photocopy, Scanning, Laminating, Binding na kuandaa nyaraka.\n\n4. 🎒 *Mauzo ya Jumla & Rejareja*\n   - Tunahudumia shule, taasisi, kampuni na wateja binafsi.\n\n👉 Andika */stationery*, */books*, au */printing* kujua zaidi. Andika */order* kuagiza sasa!";

            case 'books':
                return "📚 *VITABU VYA SHULE / SCHOOL BOOKS*\n\nSisi ni wakala wa vitabu vya shule Tanzania! Tuna stoki kamili ya mtaala wa NECTA:\n\n• 🧒 *Nursery & Pre-School*: Vitabu vya herufi, namba, kuchora na stadi za awali.\n• 🏫 *Primary School (Darasa 1-7)*: Vitabu vya kiada na ziada vya masomo yote.\n• 📗 *Secondary School / O-Level (Form 1-4)*: Physics, Chemistry, Biology, Mathematics, Geography, History, English, Kiswahili na zaidi.\n• 🎓 *A-Level (Form 5-6)*: Vitabu vya tahasusi (Combinations) zote — PCM, PCB, EGM, HGL, HKL nk.\n\n📖 *Review Books & Past Papers*: Tunazo past papers zenye majibu za mitihani ya NECTA kwa Standard 4, 7, Form 2, 4 na 6.\n\n👉 Andika */revision* kupata zaidi kuhusu vitabu vya marudio.\n👉 Andika */order* kuagiza vitabu unavyovihitaji mara moja!";

            case 'location':
                return "📍 *MAHALI TULIPO / OUR LOCATIONS*\n\nKaribu ututembelee katika matawi yetu mawili hapa Dar es Salaam:\n\n1. 🏢 *Tawi la Ubungo*\n   - Soko Kubwa la Kimataifa la Ubungo (EACLC), Dar es Salaam.\n   - Karibu na lango kuu la soko, upande wa kulia ukiingia.\n\n2. 🏢 *Tawi la Kimara*\n   - Kimara Stopover, Dar es Salaam.\n   - Karibu na kituo cha mwendo wa haraka cha Kimara.\n\n⏰ *Muda wa Kazi*:\n   - Jumatatu - Ijumaa: Saa 2:00 asubuhi - Saa 2:30 usiku (8AM - 8:30PM)\n   - Jumamosi & Jumapili: Saa 3:00 asubuhi - Saa 2:00 usiku (9AM - 8PM)\n\n📞 Simu: *0794 467 694*\n\n👉 Huwezi kufika? Andika */delivery* ili tukuletee mzigo ulipo Tanzania yote!";

            case 'delivery':
                return "🚚 *HUDUMA YA USAFIRISHAJI / DELIVERY SERVICES*\n\nTunatuma vifaa na vitabu maeneo yote ya Tanzania:\n\n1. 🏙️ *Ndani ya Dar es Salaam*:\n   - Bodaboda/Bajaji inaleta mpaka ulipo (ndani ya masaa 2-4).\n   - Gharama: TZS 3,000 hadi TZS 5,000 (kulingana na umbali).\n\n2. 🚌 *Mikoani (Upcountry Delivery)*:\n   - Tunatuma kwa njia ya mabasi ya uhakika (Shabiby, Abood, Hood, BM, nk) au Courier Services (DHL, EMS).\n   - Gharama ya usafiri: Kuanzia TZS 5,000 (utachukua kwenye stendi ya basi mkoani kwako).\n\n🛒 *Jinsi ya Kuagiza*: Andika */order* sasa kuweka oda yako!";

            case 'printing':
                return "🖨️ *HUDUMA ZA UCHAPISHAJI / PRINTING & DOCUMENT SERVICES*\n\nTunatoa huduma kamili za uchapishaji na usimamizi wa nyaraka:\n\n• 🖨️ *Printing (B&W)*: Uchapishaji wa kawaida wa haraka na bei nafuu.
• 🎨 *Color Printing*: Uchapishaji wa rangi wa ubora wa juu.
• 📋 *Photocopy*: Kunakilisha nyaraka haraka na kwa usahihi.
• 🔍 *Scanning*: Kubadilisha nyaraka za karatasi kuwa faili za PDF/digital.
• 🛡️ *Laminating*: Kulinda nyaraka muhimu (cheti, vitambulisho nk).
• 📎 *Binding*: Kushona na kufunga vitabu, ripoti na thesis (spiral & hard binding).
• 📄 *Kuandaa Nyaraka*: Tunasaidia kubadilisha na kuandaa nyaraka mbalimbali, ikiwemo kubadilisha cheti cha kuzaliwa kwenye mfumo mpya wa serikali.\n\n👉 Tuma nyaraka zako (PDF au picha) kupitia WhatsApp hii na tutakusaidia haraka!\n👉 Andika */support* kuongea na mchapishaji wetu moja kwa moja.";

            case 'wholesale':
                return "📦 *MAUZO YA JUMLA / WHOLESALE ORDERS*\n\nJe, unamiliki shule, duka la vitabu, au unahitaji vifaa kwa ajili ya taasisi/mradi wako?\n\n• *Punguzo la Bei*: Tunatoa punguzo hadi *15% - 20%* kwa wanunuzi wa jumla na shule.\n• *Uwasilishaji*: Tunapeleka mzigo hadi shuleni au dukani kwako (kwa oda kubwa za Dar na mikoani).\n• *Uaminifu*: Bidhaa zote ni halisi na zina viwango vya juu.\n\n👉 Omba nukuu ya bei kwa kuandika */quotation* au wasiliana moja kwa moja na meneja mauzo wetu kwa kuandika */support*!";

            case 'quotation':
                return "📄 *NUKUU YA BEI / PROFORMA & QUOTATION*\n\nKupata Proforma Invoice au Quotation rasmi kwa ajili ya Shule au Kampuni yako, tafadhali tumia hatua hizi:\n\n1. Andika orodha ya vitabu/vifaa unavyohitaji na idadi yake (mfano: Daftari A4 Counter Book 3 Quire - Box 5).\n2. Tuma jina kamili la Shule/Taasisi na anwani (mfano: TRUMARK High School, S.L.P 123, Dar es Salaam).\n3. Tuma maelezo haya hapa, kisha andika */support* ili mhasibu wetu ayapokee na kukuandalia nukuu rasmi ndani ya muda mfupi!";

            // Info
            case 'hours':
                return "⏰ *MUDA WA KAZI / WORKING HOURS*\n\nTuko wazi kukuhudumia siku zote za wiki, ikiwemo wikendi!\n\n• 📅 *Jumatatu hadi Ijumaa*: Saa 2:00 Asubuhi hadi Saa 2:30 Usiku (8:00AM - 8:30PM)\n• 📅 *Jumamosi*: Saa 3:00 Asubuhi hadi Saa 2:00 Usiku (9:00AM - 8:00PM)\n• 📅 *Jumapili*: Saa 3:00 Asubuhi hadi Saa 2:00 Usiku (9:00AM - 8:00PM)\n\n📍 *Matawi yetu*:\n   - Ubungo: Soko Kubwa la Kimataifa la Ubungo (EACLC)\n   - Kimara: Kimara Stopover\n\n📞 *Simu*: 0794 467 694\n\n✅ Hata wikendi tuko hapa kukusaidia! Karibu sana.";

            case 'payment':
                return "💳 *NJIA ZA MALIPO / PAYMENT METHODS*\n\nTunapokea malipo kupitia njia zifuatazo:\n\n1. 💵 *Cash (Pesa Taslimu)*: Lipa moja kwa moja katika tawi letu la Ubungo au Kimara.\n\n2. 📱 *M-Pesa*: Tuma pesa kwenye namba yetu ya biashara. Andika */support* kupata namba.\n\n3. 📱 *Tigo Pesa*: Tuma pesa kwenye namba yetu. Andika */support* kupata namba.\n\n4. 📱 *Airtel Money*: Tuma pesa kwenye namba yetu ya Airtel. Andika */support* kupata namba.\n\n5. 🏦 *Bank Transfer*: Tunatoa namba ya akaunti ya benki unapoagiza. Andika */support* kupata maelezo ya benki.\n\n✅ *Nyaraka za Malipo*: Tunatoa risiti, invoice, quotation na nyaraka zote za auditing bila malipo ya ziada.\n\n⚠️ Baada ya kulipa, tuma picha ya muamala hapa ili tuthibitishe na kuanza mzigo wako mara moja!";

            case 'catalog':
                return "📑 *KATALOGI YA BIDHAA / PRODUCT CATALOG*\n\nTunaandaa katalogi ya kisasa yenye bidhaa na bei zetu zote za hivi karibuni. \n\nKwa sasa, tafadhali andika jina la kitabu au vifaa unavyohitaji hapa, na tutakupa picha na bei zake mara moja. Unaweza pia kuandika */pricing* kuona bei za vifaa maarufu au */support* kuongea na mhudumu wetu.";

            case 'trust':
                return "⭐ *KWANINI UCHAGUE TRUMARK? / WHY CHOOSE TRUMARK?*\n\nTRUMARK Stationery & Books ni biashara ya kuaminika Tanzania kwa sababu zifuatazo:\n\n1. ✅ *Bidhaa za Asili (Original Products)*: Tunauza bidhaa original za ubora wa juu. Tuepuka nakala na bidhaa duni.\n2. 💰 *Bei Nzuri*: Bei zetu ni za ushindani na zinafaa kwa wazazi, walimu, shule na kampuni.\n3. ⚡ *Huduma ya Haraka*: Tunahudumia haraka — delivery, printing na maswali yote tunayajibu kwa wakati.\n4. 📄 *Nyaraka Kamili*: Tunatoa risiti, invoice, quotation na nyaraka zote za biashara kwa uhakika.\n5. 🚚 *Delivery Yote Tanzania*: Tunatuma bidhaa mikoa yote ya Tanzania bila tatizo.\n6. 🤝 *Uaminifu wa Kweli*: Biashara yetu inajengwa juu ya uaminifu, kuheshimu wateja na kuhakikisha unachohitaji unakipata kwa wakati.\n\n👉 Jaribu leo — utapendezwa na huduma yetu! Andika */order* au */support*!";

            case 'pricing':
                return "💰 *BEI ZA BIDHAA MAARUFU / PRICE LIST*\n\nHapa kuna bei za baadhi ya vifaa vyetu maarufu (Mauzo ya Reja reja):\n\n• 📑 *Karatasi za Print (A4 Reams)*: TZS 11,500 hadi 13,000 (kulingana na chapa - Double A, PaperOne nk).\n• 📓 *Daftari za Counter (3 Quire)*: TZS 2,500 kila moja.\n• 📓 *Daftari za Counter (4 Quire)*: TZS 3,200 kila moja.\n• 🖊️ *Kalamu (Boksi la kalamu 50 - Bic/Speedo)*: TZS 8,000 hadi 10,000.\n• 📖 *Vitabu vya Mazoezi (Exercise Books - A5)*: TZS 500 kila kimoja.\n• 🗂️ *Faili za Ofisi (Box Files)*: TZS 3,500 hadi 5,000 kila moja.\n\n⚠️ *Kumbuka*: Bei za jumla (Wholesale) zina punguzo kubwa! Andika */wholesale* kujua zaidi.";

            // Edu & Products
            case 'stationery':
                return "✏️ *VIFAA VYA OFISI NA SHULE / STATIONERY*\n\nTRUMARK tuna vifaa vyote vya shule na ofisi vya ubora wa juu:\n\n• 📑 *Karatasi (Paper)*: A4 Reams (Double A, PaperOne, Supreme nk), A3, karatasi za rangi, manila paper.\n• 📓 *Madaftari (Exercise/Counter Books)*: Counter books (1-4 Quire), Exercise books, Sketchbooks, Diaries.\n• ✒️ *Vifaa vya Kuandika*: Kalamu (Bic, Speedo, Pilot), penseli, markers za rangi, highlighters, chaki.\n• 🎒 *School Bags*: Mabegi ya shule ya ubora mzuri na ya kudumu kwa watoto wa nursery hadi sekondari.\n• 🗂️ *Vifaa vya Ofisi*: Box files, spring files, staplers, punch machines, rulers, makasi, gundi, stampu.\n• 🧮 *Vifaa vya Hesabu*: Mathematical sets na CASIO Scientific Calculators (halisi zenye warranty).\n\n👉 Sema bidhaa unayotaka ili tukufahamishe bei, au andika */pricing* kuona orodha ya bei maarufu!";

            case 'revision':
                return "📖 *VITABU VYA MARUDIO NA PAST PAPERS / REVISION BOOKS*\n\nMsaidie mwanafunzi kufanya vizuri katika mitihani ya NECTA kwa kutumia vitabu vyetu vya marudio:\n\n• 🏫 *Darasa la 4 & 7 (Standard 4 & 7)*: Past papers zenye majibu ya masomo yote (Sayansi, Hesabu, Kiswahili, English, nk).\n• 🎒 *Form 2 & Form 4 (O-Level)*: Solved Past Papers za miaka 10 iliyopita, Miongozo ya kujibu maswali ya mitihani.\n• 🎓 *Form 6 (A-Level)*: Vitabu vya marudio vya masomo ya sayansi na sanaa kulingana na tahasusi (PCM, PCB, PGM, HGL, HKL, EGM, nk).\n\n👉 Andika somo au darasa unalotaka ili kupata maelezo na bei ya vitabu husika!";

            case 'subjects':
                return "🔬 *MASOMO TUNAYOYAHUDUMIA / SUBJECTS*\n\nTuna vitabu vya masomo yote ya shule:\n\n1. 🧮 *Hesabu & Sayansi*: Mathematics, Physics, Chemistry, Biology, Information Technology (ICT).\n2. 🌍 *Sanaa & Jamii*: Geography, History, Civics, General Studies.\n3. 🗣️ *Lugha (Languages)*: English, Kiswahili, French, Arabic.\n4. 💼 *Biashara*: Commerce, Bookkeeping, Economics.\n\n👉 Andika masomo unayotaka kununulia vitabu, au andika */support* uongee na mhudumu wetu.";

            case 'schoolpacks':
                return "🎒 *VIFURUSHI VYA SHULE / BACK-TO-SCHOOL PACKS*\n\nOkoa muda na fedha kwa kununua vifurushi vyetu vilivyoandaliwa tayari kwa ajili ya mwanafunzi wako:\n\n1. 🧸 *Kifurushi cha Nursery (TZS 15,000)*:\n   - Kalamu za rangi, daftari la kuchora, herufi, namba na penseli.\n\n2. ✏️ *Kifurushi cha Primary (TZS 35,000)*:\n   - Daftari 12, Kalamu 10, Penseli, Rula, Seti ya hesabu, Kifutio na cherezo.\n\n3. 📚 *Kifurushi cha Secondary (TZS 55,000)*:\n   - Daftari za Counter book 6, Kalamu 12, Seti ya Hesabu (Mathematical Set), Scientific Calculator, rula na box file.\n\n👉 *Jinsi ya kuagiza*: Taja kifurushi unachotaka, kisha andika */order* ili tukuletee mzigo popote ulipo!";

            // Customer Service
            case 'order':
                $stateKey = "wa_state_" . preg_replace('/[^0-9]/', '', $customerPhone);
                \Illuminate\Support\Facades\Cache::put($stateKey, ['step' => 'awaiting_order_items', 'data' => []], now()->addMinutes(30));
                return "🛒 *HATUA YA 1/2: Orodha ya Vifaa / Order Items*\n\nTafadhali andika hapa orodha ya vitabu au vifaa unavyotaka kununua na idadi yake:\n*(Mfano: Daftari za Counter Quire 3 nakala 5, Kalamu za Bic boksi 1)*";

            case 'feedback':
                $stateKey = "wa_state_" . preg_replace('/[^0-9]/', '', $customerPhone);
                \Illuminate\Support\Facades\Cache::put($stateKey, ['step' => 'awaiting_feedback', 'data' => []], now()->addMinutes(30));

                $buttons = [
                    ['id' => 'feedback_5_stars', 'title' => '⭐⭐⭐⭐⭐ Safi sana'],
                    ['id' => 'feedback_3_stars', 'title' => '⭐⭐⭐ Wastani'],
                    ['id' => 'feedback_1_star', 'title' => '⭐ Changamoto'],
                ];
                $body = "⭐ *JE, UMERIDHIKA NA HUDUMA YETU?*\n\nTafadhali chagua kiwango cha kuridhika kwako na huduma za TRUMARK leo:";
                $this->whatsapp->sendInteractiveButtons($customerPhone, $body, $buttons, '', 'TRUMARK Feedback');
                return true;

            case 'track':
                return "🔍 *KUFUATILIA MZIGO / ORDER TRACKING*\n\nJe, tayari umeshafanya malipo na unataka kujua hatua ya mzigo wako?\n\n• *Ndani ya Dar es Salaam*: Mzigo unatumwa ndani ya masaa 2-4 baada ya malipo. Tutakupigia simu bodaboda/bajaji akiondoka.\n• *Mikoani*: Mara baada ya kukabidhi mzigo kwenye basi, tutakutumia **picha ya risiti (Waybill)** yenye namba ya simu ya dereva wa basi hapa WhatsApp.\n\n👉 Kama unahitaji msaada wowote kuhusu ufuatiliaji wa mzigo, andika tu */support* na tutakusaidia mara moja!";

            case 'help':
            case 'menu':
                $sections = [
                    [
                        'title' => 'Vitabu vya Shule',
                        'rows' => [
                            ['id' => '/books_primary', 'title' => 'Darasa la 1-7 (Primary)', 'description' => 'Vitabu vya mtaala mpya Standard 1-7'],
                            ['id' => '/books_secondary', 'title' => 'Secondary & A-Level', 'description' => 'Form 1 hadi 6 masomo yote'],
                            ['id' => '/revision', 'title' => 'Past Papers & Reviews', 'description' => 'Mitihani ya taifa na miongozo ya NECTA'],
                        ]
                    ],
                    [
                        'title' => 'Vifaa vya Shule & Ofisi',
                        'rows' => [
                            ['id' => '/stationery', 'title' => 'Stationery Categories', 'description' => 'Madaftari, Kalamu, Karatasi za printa, Faili'],
                            ['id' => '/calc', 'title' => 'Scientific Calculators', 'description' => 'Calculators za CASIO halisi zenye warranty'],
                            ['id' => '/schoolpacks', 'title' => 'Back to School Packs', 'description' => 'Vifurushi vya bei nafuu Nursery hadi Secondary'],
                        ]
                    ],
                    [
                        'title' => 'Huduma na Habari',
                        'rows' => [
                            ['id' => '/printing', 'title' => 'Printing & Photocopy', 'description' => 'Spiral/Hard binding, scanning, laminating'],
                            ['id' => '/location', 'title' => 'Matawi & Mahali tulipo', 'description' => 'Matawi Ubungo EACLC na Kimara Stopover'],
                            ['id' => '/payment', 'title' => 'Njia za Malipo', 'description' => 'Namba za malipo na benki'],
                        ]
                    ]
                ];

                $body = "👋 *TRUMARK MAIN MENU / MENU KUU*\n\nKaribu kwenye duka letu mtandaoni! Tafadhali fungua orodha hapa chini kuchagua huduma unayohitaji haraka:";
                $this->whatsapp->sendListMessage($customerPhone, $body, "Fungua Orodha", $sections, '', 'TRUMARK Co. LTD');
                return true;

            case 'welcome':
                return "👋 *KARIBU TRUMARK CO. LTD! / WELCOME TO TRUMARK!*\n\nHabari! Sisi ni wauzaji wa vitabu vyote vya shule, vifaa vya ofisini/shuleni na watoaji wa huduma bora za printing na photocopy Tanzania. 😊\n\nAndika neno lolote hapa kuuliza swali, au chagua huduma unayohitaji kwa kuandika amri hizi:\n\n📦 *Bidhaa & Vifaa (Products & Catalog)*:\n👉 Andika */products* - Kuona bidhaa zetu zote.\n👉 Andika */books* - Kujua vitabu vya shule tunavyouza.\n👉 Andika */stationery* - Kuona vifaa vya ofisi na shule.\n👉 Andika */schoolpacks* - Vifurushi vya Back-to-School.\n\n🚚 *Oda & Malipo (Order & Delivery)*:\n👉 Andika */order* - Jinsi ya kuweka oda yako ya haraka.\n👉 Andika */delivery* - Maelezo ya kutumiwa mzigo.\n👉 Andika */payment* - Njia za kufanya malipo na namba zetu.\n\n📍 *Ofisi & Mawasiliano*:\n👉 Andika */location* - Kupata ramani na matawi yetu Ubungo & Kimara.\n👉 Andika */support* - Ongea na Mhudumu wetu (Live Support).\n👉 Andika */menu* - Kuona menu kuu yenye orodha safi.\n\nTRUMARK inakujali! Tunakutakia siku njema na manunuzi mema! 🌟";

            // Expanded Sub-commands
            case 'books_primary':
                return "📚 *VITABU VYA SHULE ZA MSINGI / PRIMARY SCHOOL BOOKS*\n\nTuna vitabu vyote vya mtaala mpya wa NECTA (Darasa la 1 - 7):\n- Mathematics (Hesabu)\n- Science & Technology (Sayansi na Teknolojia)\n- Social Studies (Maarifa ya Jamii)\n- English & Kiswahili\n- Civic & Moral Education (Uraia na Maadili)\n- Vocational Skills (Stadi za Kazi)\n\n👉 Andika */order* kuagiza vitabu hivi!";

            case 'books_secondary':
                return "📚 *VITABU VYA SEKONDARI / SECONDARY SCHOOL BOOKS*\n\nTuna vitabu vya O-Level (Form 1-4) na A-Level (Form 5-6):\n- Physics, Chemistry, Biology\n- Pure Mathematics & Basic Mathematics\n- Geography, History, Civics\n- English, Kiswahili, Literature in English\n- Bookkeeping, Commerce, Economics\n\n👉 Andika masomo unayotaka kuagiza, kisha andika */order*!";

            case 'pastpapers_o':
                return "📝 *PAST PAPERS ZA O-LEVEL (FORM 2 & 4)*\n\nJiandae vizuri na mitihani ya NECTA kwa Solved Past Papers za miaka 10 iliyopita:\n- Masomo yote ya Sayansi na Sanaa.\n- Miongozo ya kujibu maswali na kupata alama za juu.\n- Bei: TZS 5,000 hadi 8,000 kwa kila kitabu cha somo husika.\n\n👉 Andika */order* kuweka oda ya vitabu hivi vya marudio!";

            case 'pastpapers_a':
                return "📝 *PAST PAPERS ZA A-LEVEL (FORM 6)*\n\nVitabu vya marudio na solved past papers za tahasusi zote za A-Level (PCM, PCB, CBG, EGM, HGE, HGL, HKL, nk):\n- Maswali na majibu ya kina ya Mitihani ya Taifa iliyopita.\n- Kujazwa na vidokezo vya NECTA.\n- Bei: Kuanzia TZS 7,000 tu kila somo.\n\n👉 Andika */order* ili tukuletee mzigo ulipo!";

            case 'exercise_books':
                return "📓 *MAJADALIANO YA DAFTARI / EXERCISE BOOKS PRICING*\n\nTuna Exercise books za chapa maarufu kama Kasuku, Sifa, na nyinginezo:\n- Daftari nyembamba (A5 Exercise Book 32/48/96 Pages): TZS 300 - TZS 800 kila moja.\n- Daftari za Quire (Counter Books A4):\n  * 1 Quire: TZS 1,200\n  * 2 Quire: TZS 1,800\n  * 3 Quire: TZS 2,500\n  * 4 Quire: TZS 3,200\n\n👉 Mauzo ya Box zima yana punguzo kubwa la jumla! Andika */wholesale* kujua zaidi.";

            case 'office_supplies':
                return "✏️ *VIFAA VYA OFISI / OFFICE STATIONERY*\n\nTuna vifaa vyote vinavyohitajika ofisini kwako kwa ajili ya utendaji bora:\n- A4 Printing paper (Chapa ya Double A, PaperOne, nk) - Boksi / Ream.\n- Faili za Ofisi (Box Files, Spring Files, Flat Files, Clear Bags).\n- Kalamu za saini (Gel pens, ball pens - Bic, Speedo, Schneider).\n- Mashine za Ofisi (Staplers, Staple removers, Paper punches, Laminating machines).\n- Wino na Tona (Printer cartridges, stamp pads & ink).\n\n👉 Andika */quotation* ili tukuandalie nukuu ya bei kwa ajili ya ofisi yako!";

            case 'calc':
                return "🧮 *CALCULATORS / KIKOKOTOO YA KISAYANSI*\n\nTuna calculators halisi za chapa ya CASIO zenye warranty kwa ajili ya wanafunzi na ofisi:\n- *Scientific Calculator (Casio fx-991EX / fx-991ES Plus)*: Kikokotoo bora cha sayansi na hesabu za sekondari/vyuo vikuu.\n  * Bei: TZS 35,000 hadi 45,000 (Halisi na imara).\n- *Standard Office Calculator (Casio/Citizen)*: Kikokotoo kikubwa kwa ajili ya biashara na hesabu za kawaida ofisini.\n  * Bei: Kuanzia TZS 15,000.\n\n👉 Andika */order* kuagiza calculator yako safi leo!";

            case 'brands':
                return "🏷️ *CHAPA TUNAZOZIELEWA / OUR BRAND PARTNERS*\n\nTunasambaza na kuuza bidhaa za chapa zinazoongoza duniani na Tanzania kwa ubora:\n- *Madaftari*: Kasuku, Sifa, TRUMARK premium.\n- *Karatasi*: Double A, PaperOne, IK Yellow, Supreme.\n- *Kalamu*: Bic, Speedo, Schneider, Pilot, Cello.\n- *Calculators*: CASIO (Original with warranty).\n- *Vifaa vingine*: Kangaroo, Deli, Rexel, Maped.\n\n✅ Kila bidhaa kwetu ni ya uhakika na asili!";

            case 'discount':
                return "🏷️ *SERA YA MAPUNGUZO / OFFERS & DISCOUNTS*\n\nTunaamini katika kutoa thamani kubwa kwa kila shilingi unayolipa TRUMARK:\n- *Wateja wa Reja reja*: Pata punguzo la hadi 5% unaponunua Back-to-school packs (/schoolpacks).\n- *Wateja wa Jumla / Shule*: Pata punguzo kubwa la *10% hadi 20%* kulingana na kiasi cha oda yako (/wholesale).\n- *Usafirishaji Bure*: Oda zote za jumla ndani ya Dar es Salaam tunakuletea bure kabisa!\n\n👉 Wasiliana na mhudumu wetu kwa kuandika */support* kupata ofa maalum!";

            case 'location_ubungo':
                return "🏢 *MAELEKEZO YA TAWI LA UBUNGO / UBUNGO BRANCH DIRECTIONS*\n\n- *Mahali*: Ubungo Plaza, Ghorofa ya Chini (Ground Floor).\n- *Jinsi ya kufika*: Kama unatumia mwendo wa haraka, shuka kituo cha Ubungo Plaza, duka letu liko upande wa kulia ukielekea lango kuu la jengo.\n- *Simu*: 0794 467 694\n\n📍 Tunafungua Jumatatu hadi Jumamosi, 8:00 AM - 6:00 PM.";

            case 'location_kimara':
                return "🏢 *MAELEKEZO YA TAWI LA KIMARA / KIMARA BRANCH DIRECTIONS*\n\n- *Mahali*: Kimara Mwisho, mkabala na kituo kikuu cha mwendo wa haraka cha Kimara.\n- *Jinsi ya kufika*: Shuka kituo cha mwendo wa haraka cha Kimara Mwisho, vuka barabara upande wa pili kuelekea jengo jipya la TRUMARK (lililoandikwa TRUMARK Stationery & Books).\n- *Simu*: 0794 467 694\n\n📍 Tunafungua Jumatatu hadi Jumamosi, 8:00 AM - 6:00 PM.";

            case 'jobs':
                return "💼 *KAZI NA NAFASI / CAREERS & INTERNSHIPS*\n\nAsante kwa nia yako ya kujiunga na timu ya TRUMARK Co. LTD!\n\nTunaongeza timu yetu mara kwa mara tunapofungua matawi mapya. \n- Kwa sasa, hatuna nafasi zilizo wazi (No vacancies).\n- Lakini tunakaribisha CV yako kwa kazi za baadae za Uuzaji (Sales), Graphic Design, nk.\n- Tuma barua pepe yako na CV kwenda: *hr@trumark.co.tz*.\n\nAsante kwa kuipenda TRUMARK!";

            case 'complaints':
                return "📢 *MALALAMIKO NA MAONI / CUSTOMER FEEDBACK & ISSUES*\n\nTRUMARK tunathamini sana maoni na kuridhika kwako. Kama kuna changamoto yoyote uliyokutana nayo:\n1. Huduma mbaya kutoka kwa mhudumu wetu.\n2. Kuchelewa kwa mzigo wako.\n3. Bidhaa iliyoharibika au isiyo sahihi.\n\nTafadhali tuma ujumbe wako hapa moja kwa moja au piga simu kwa meneja wa huduma kwa wateja kwa namba: *0794 467 694*.\n\nTunaahidi kutatua changamoto yako ndani ya masaa 24! Uaminifu wako ni furaha yetu. ❤️";

            case 'refund':
                return "🔄 *SERA YA KURUDISHA BIDHAA / RETURN & REFUND POLICY*\n\nSera yetu ya kurudisha bidhaa ni rahisi na yenye usawa:\n- *Muda*: Bidhaa inaweza kurudishwa au kubadilishwa ndani ya **siku 3** tangu tarehe ya ununuzi.\n- *Hali ya Bidhaa*: Bidhaa lazima iwe katika hali yake ya awali (haijatumika wala kuharibiwa, na risiti yake iwepo).\n- *Kubadilisha (Exchange)*: Unaweza kubadilisha bidhaa kwa bidhaa nyingine yenye thamani sawa.\n- *Kurudishiwa Pesa (Refund)*: Pesa inarudishwa endapo bidhaa ilikuwa na kasoro kutoka kiwandani na hatuna nyingine ya kuibadilisha.\n\n👉 Kwa maelezo zaidi ya bidhaa yako, andika */support*!";

            case 'contact':
            case 'support':
                return $this->triggerHumanHandoff($customerPhone);

            default:
                // Fallback directly to support instead of showing error
                return $this->triggerHumanHandoff($customerPhone);
        }
    }

    /**
     * Hand-off to human support
     */
    protected function triggerHumanHandoff($customerPhone)
    {
        $adminPhone = env('WHATSAPP_ADMIN_PHONE');

        // Notify admin silently
        if ($adminPhone) {
            $alertMsg = "🚨 *Support Request Alert!*\nCustomer +{$customerPhone} needs human assistance.\nLink to message them: https://wa.me/{$customerPhone}";
            $this->whatsapp->sendMessage($adminPhone, $alertMsg);
        }

        // Return user response
        return "🤝 *TRUMARK Customer Support / Huduma kwa Wateja* 😊\n\n📞 *Piga Simu / WhatsApp*: 0794 467 694\n\nTunakusaidia na:\n✅ Oda za vitabu na vifaa\n✅ Delivery na usafirishaji\n✅ Bei na quotation\n✅ Huduma za Printing & Photocopy\n✅ Nyaraka na risiti za biashara\n✅ Malalamiko yoyote\n\n📍 *Matawi yetu*:\n   - Ubungo: Soko Kubwa la Kimataifa la Ubungo (EACLC)\n   - Kimara: Kimara Stopover\n\n⏰ *Wazi*: Jumatatu-Ijumaa (8AM-8:30PM) | Jumamosi-Jumapili (9AM-8PM)\n\n💬 Jibu hapa au bonyeza kuzungumza na mhudumu wetu moja kwa moja:\nhttps://wa.me/255794467694";
    }

    /**
     * AI Fallback via Google Gemini API
     */
    protected function askGeminiAI($text)
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return "Samahani, sijaelewa. (AI is currently offline). Tafadhali tumia /help kuona maelekezo, au /support kuongea na mhudumu wetu.";
        }

        $systemPrompt = "You are the official AI assistant for TRUMARK Stationery & Books, a trusted business in Dar es Salaam, Tanzania. Always respond in Swahili first, with simple English clarification when needed. Be short, helpful, professional, warm, and friendly.

KEY BUSINESS FACTS (always use these, never guess):
- Business Name: TRUMARK Stationery & Books
- Phone: 0794 467 694
- Branch 1: Soko Kubwa la Kimataifa la Ubungo (EACLC), Dar es Salaam
- Branch 2: Kimara Stopover, Dar es Salaam
- Hours: Mon-Fri 8:00AM-8:30PM | Sat-Sun 9:00AM-8:00PM (open 7 days a week)
- Products: School books (Nursery, Primary, Secondary O-Level, A-Level, NECTA), review books, past papers, stationery (daftari, kalamu, penseli, karatasi za printa/reams, school bags), office supplies
- Services: Printing (B&W & Color), Photocopy, Scanning, Laminating, Binding, Document preparation (including birth certificate conversion to new government format)
- Payment: Cash, M-Pesa, Tigo Pesa, Airtel Money, Bank Transfer
- Documents provided: Risiti, Invoice, Quotation, Proforma — all provided free of charge
- Sales: Both jumla (wholesale) and rejareja (retail) — serves schools, institutions, companies, and individuals
- Delivery: Inside Dar es Salaam (bodaboda/bajaji) and all regions of Tanzania (via bus/courier)

If a user asks anything outside these services, politely redirect them. If unclear, ask a follow-up question. Do not make up prices unless specifically asked — then give approximate ranges. Keep responses under 200 words.";


        try {
            $response = \Illuminate\Support\Facades\Http::post("https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    // Simulate system context via conversation turns
                    [
                        'role' => 'user',
                        'parts' => [['text' => $systemPrompt . "\n\nCustomer message: " . $text]]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 200,
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['candidates'][0]['content']['parts'][0]['text'] ?? "Samahani, nimeshindwa kupata jibu kwa sasa. Tumia /support.";
            }

            Log::error('Gemini API Error: ' . $response->body());
            return "Samahani, mtandao wetu uko chini kidogo. Tafadhali tumia /support.";

        } catch (\Exception $e) {
            Log::error('Gemini Request Failed: ' . $e->getMessage());
            return "Samahani, nimeshindwa kuunganishwa. Tafadhali tumia /support.";
        }
    }

    /**
     * Intercept and handle active state machine conversational flows (orders & feedback)
     */
    protected function handleStateFlow($from, $text, $customer)
    {
        $stateKey = "wa_state_" . preg_replace('/[^0-9]/', '', $from);
        $state = \Illuminate\Support\Facades\Cache::get($stateKey);

        if (!$state) {
            return null;
        }

        $step = $state['step'] ?? 'idle';
        $data = $state['data'] ?? [];

        switch ($step) {
            // === ORDER STATE MACHINE ===
            case 'awaiting_order_items':
                $data['items'] = $text;
                $state['step'] = 'awaiting_order_delivery_method';
                $state['data'] = $data;
                \Illuminate\Support\Facades\Cache::put($stateKey, $state, now()->addMinutes(30));

                $buttons = [
                    ['id' => 'delivery_pickup_ubungo', 'title' => '🏢 Ubungo EACLC'],
                    ['id' => 'delivery_pickup_kimara', 'title' => '🏢 Kimara Stopover'],
                    ['id' => 'delivery_home', 'title' => '🚚 Delivery (Ulipo)'],
                ];

                $body = "🛒 *HATUA YA 2/2: Usafirishaji / Delivery*\n\nJe, utakuja kuchukua bidhaa zako kwenye matawi yetu wenyewe, au ungependa tukuletee (Delivery)?\n\nTafadhali chagua hapa chini:";
                $this->whatsapp->sendInteractiveButtons($from, $body, $buttons, '', 'TRUMARK Orders');
                return true;

            case 'awaiting_order_delivery_method':
                $method = '';
                if ($text === 'delivery_pickup_ubungo') {
                    $method = 'Pickup - Ubungo EACLC';
                } elseif ($text === 'delivery_pickup_kimara') {
                    $method = 'Pickup - Kimara Stopover';
                } elseif ($text === 'delivery_home') {
                    $method = 'Home/Office Delivery';
                } else {
                    $method = $text;
                }

                $data['delivery_method'] = $method;

                if ($text === 'delivery_home' || str_contains(strtolower($text), 'delivery')) {
                    $state['step'] = 'awaiting_delivery_address';
                    $state['data'] = $data;
                    \Illuminate\Support\Facades\Cache::put($stateKey, $state, now()->addMinutes(30));

                    return "🚚 *Anwani ya Delivery*\n\nTafadhali andika **Eneo lako unapoishi / Ofisi** na **Jina kamili la Mpokeaji**:";
                } else {
                    \Illuminate\Support\Facades\Cache::forget($stateKey);
                    $this->completeOrder($from, $data, $customer);

                    return "✅ *Oda Yako Imepokelewa kwa Ufanisi!*\n\n📍 *Njia ya Kuchukulia*: {$method}\n📦 *Orodha ya Vifaa*: {$data['items']}\n\nMhudumu wetu anaanza kuandaa mzigo wako na atakupigia simu au kukutumia maelekezo ya malipo hapa WhatsApp hivi punde. Asante kwa kuchagua TRUMARK! 😊";
                }

            case 'awaiting_delivery_address':
                $data['address'] = $text;
                \Illuminate\Support\Facades\Cache::forget($stateKey);
                $this->completeOrder($from, $data, $customer);

                return "✅ *Oda Yako Imepokelewa kwa Ufanisi!*\n\n📍 *Mahali pa kuletewa*: {$text}\n📦 *Orodha ya Vifaa*: {$data['items']}\n\nMhudumu wetu anaanza kuandaa mzigo wako na atakupigia simu au kukutumia maelekezo ya malipo hapa WhatsApp hivi punde. Asante kwa kuchagua TRUMARK! 😊";

            // === FEEDBACK STATE MACHINE ===
            case 'awaiting_feedback':
                $rating = 5;
                if (str_contains($text, '5_stars') || str_contains($text, 'Safi')) {
                    $rating = 5;
                } elseif (str_contains($text, '3_stars') || str_contains($text, 'Wastani')) {
                    $rating = 3;
                } elseif (str_contains($text, '1_star') || str_contains($text, 'Changamoto')) {
                    $rating = 1;
                } else {
                    $rating = intval(preg_replace('/[^0-9]/', '', $text)) ?: 5;
                }

                $data['rating'] = $rating;
                $state['step'] = 'awaiting_feedback_comment';
                $state['data'] = $data;
                \Illuminate\Support\Facades\Cache::put($stateKey, $state, now()->addMinutes(30));

                return "⭐ *Hatua ya 2/2: Maoni ya ziada / Extra Comments*\n\nAsante kwa kiwango ulichochagua! Tafadhali andika maoni yako mafupi, ushauri au mapendekezo ili tushughulikie (au andika 'Hapana' kama huna):";

            case 'awaiting_feedback_comment':
                $rating = $data['rating'] ?? 5;
                $comment = $text;

                try {
                    \App\Models\CustomerFeedback::create([
                        'customer_id' => $customer ? $customer->id : Customer::firstOrCreate(['phone' => $from], ['name' => 'WhatsApp Customer'])->id,
                        'rating' => $rating,
                        'comment' => (strtolower($comment) === 'hapana') ? null : $comment,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to save customer feedback: " . $e->getMessage());
                }

                \Illuminate\Support\Facades\Cache::forget($stateKey);
                return "🙏 *Asante sana kwa maoni yako!* Yatusaidia kuboresha huduma zetu na kuhakikisha TRUMARK inabaki kuwa duka lako pendwa la vitabu na vifaa daima. Ubarikiwe sana!";
        }

        return null;
    }

    /**
     * Complete order and alert Admin
     */
    protected function completeOrder($from, $data, $customer)
    {
        $adminPhone = env('WHATSAPP_ADMIN_PHONE');
        $delivery = $data['delivery_method'] ?? 'Pickup';
        $address = $data['address'] ?? 'N/A';
        $items = $data['items'] ?? 'N/A';

        if ($adminPhone) {
            $alertMsg = "🛒 *Oda Mpya ya WhatsApp!*\n\n"
                . "👤 *Mteja*: +{$from}\n"
                . "📦 *Bidhaa*: {$items}\n"
                . "🚚 *Njia*: {$delivery}\n"
                . "📍 *Anwani/Tawi*: {$address}\n\n"
                . "Tafadhali wasiliana na mteja kukamilisha malipo na usafirishaji: https://wa.me/{$from}";
            $this->whatsapp->sendMessage($adminPhone, $alertMsg);
        }
    }
}
