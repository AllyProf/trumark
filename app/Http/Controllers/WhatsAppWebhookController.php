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
        
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
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
            // Basic processing of incoming messages
            if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
                $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
                $from    = $message['from'];
                $text    = trim($message['text']['body'] ?? '');

                Log::info("[WA-BOT] Message received from $from: \"$text\"");

                if (empty($text)) {
                    Log::info('[WA-BOT] Non-text message ignored.');
                    return response('OK', 200);
                }

                // Find customer by phone
                $cleanPhone = preg_replace('/[^0-9]/', '', $from);
                $customer   = Customer::where('phone', 'like', "%$cleanPhone%")->first();

                // Log incoming message to CRM
                SmsLog::create([
                    'customer_id' => $customer ? $customer->id : null,
                    'phone'       => $from,
                    'message'     => "INCOMING: " . $text,
                    'status'      => 'received',
                    'response'    => json_encode($message),
                ]);

                // ROUTING: 1. Ice Breakers -> 2. Commands -> 3. Keyword Matcher -> 4. AI Fallback
                $responseMessage = $this->handleIceBreaker($text);
                if ($responseMessage) {
                    Log::info('[WA-BOT] Matched ICE BREAKER.');
                } elseif (str_starts_with($text, '/')) {
                    Log::info('[WA-BOT] Routing to COMMAND handler.');
                    $responseMessage = $this->handleCommand($text, $from);
                } else {
                    // Try to match keywords before falling back to Gemini
                    $matchedCommand = $this->handleKeywordMatch($text);
                    if ($matchedCommand) {
                        Log::info("[WA-BOT] Matched KEYWORD intent: $matchedCommand");
                        $responseMessage = $this->handleCommand($matchedCommand, $from);
                    } else {
                        Log::info('[WA-BOT] No match — falling back to GEMINI AI.');
                        $responseMessage = $this->askGeminiAI($text);
                    }
                }

                Log::info('[WA-BOT] Response to send: ' . ($responseMessage ?? 'NULL'));

                // Dispatch reply
                if ($responseMessage) {
                    $sendResult = $this->whatsapp->sendMessage($from, $responseMessage);
                    Log::info('[WA-BOT] WhatsApp send result: ' . json_encode($sendResult));

                    SmsLog::create([
                        'customer_id' => $customer ? $customer->id : null,
                        'phone'       => $from,
                        'message'     => "[BOT REPLY] " . $responseMessage,
                        'status'      => $sendResult['success'] ? 'sent' : 'failed',
                    ]);
                }
            } else {
                // Status update (delivery receipts, etc.) — just acknowledge
                Log::info('[WA-BOT] Non-message webhook event received (status update or other).');
            }

        } catch (\Throwable $e) {
            Log::error('[WA-BOT] EXCEPTION in handle(): ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
        }

        return response('OK', 200);
    }

    /**
     * Exact matches for Ice Breaker Buttons
     */
    protected function handleIceBreaker($text)
    {
        $iceBreakers = [
            'School Books / Vitabu vya Shule' => "📚 We offer a wide range of School Books for Nursery, Primary, Secondary, and A-Level! Type /books for details.",
            'School Books' => "📚 We offer a wide range of School Books for Nursery, Primary, Secondary, and A-Level! Type /books for details.",
            'Stationery & Office Supplies' => "🛒 TRUMARK provides top-quality stationery and office supplies. Type /stationery for categories or /wholesale for bulk orders.",
            'Stationery' => "🛒 TRUMARK provides top-quality stationery and office supplies. Type /stationery for categories or /wholesale for bulk orders.",
            'Delivery Information / Usafirishaji' => "🚚 We deliver inside and outside Tanzania! Type /delivery to see our delivery options and times.",
            'Delivery Information' => "🚚 We deliver inside and outside Tanzania! Type /delivery to see our delivery options and times.",
            'Delivery' => "🚚 We deliver inside and outside Tanzania! Type /delivery to see our delivery options and times.",
            'Customer Support / Huduma kwa Wateja' => "💬 TRUMARK Customer Support is here to help! Type /support to connect with a representative.",
            'Customer Support' => "💬 TRUMARK Customer Support is here to help! Type /support to connect with a representative.",
            'Support' => "💬 TRUMARK Customer Support is here to help! Type /support to connect with a representative."
        ];

        // Case-insensitive exact match
        foreach ($iceBreakers as $breaker => $reply) {
            if (strtolower(trim($text)) === strtolower($breaker)) {
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
                return "📦 *BIDHAA ZETU / OUR PRODUCTS*\n\nTunajivunia kutoa bidhaa bora za kielimu na ofisi:\n\n1. 📚 *Vitabu vya Shule (School Books)*\n   - Mtaala mpya wa NECTA (Nursery, Primary, Secondary & A-Level).\n   - Vitabu vya marudio na Past Papers zote (/revision).\n\n2. ✏️ *Vifaa vya Ofisi na Shule (Stationery)*\n   - Daftari, kalamu, A4 paper reams, faili, nk (/stationery).\n\n3. 🖨️ *Huduma za Uchapishaji (Printing Services)*\n   - Photocopy, Color Printing, Binding & Lamination (/printing).\n\n4. 🎒 *Vifurushi vya Shule (Back to School Packs)*\n   - Pata vifaa vyote kwa punguzo kubwa (/schoolpacks).\n\n👉 Andika bidhaa unayotaka au chagua amri husika kujua zaidi!";

            case 'books':
                return "📚 *VITABU VYA SHULE / SCHOOL BOOKS*\n\nTuna stoki kamili ya vitabu vya mtaala wa NECTA:\n\n• *Nursery & Pre-School*: Vitabu vya herufi, namba, kuchora na stadi za awali.\n• *Primary School (Standard 1-7)*: Vitabu vya kiada na ziada vya masomo yote.\n• *Secondary School (Form 1-4)*: Physics, Chemistry, Biology, Mathematics, Geography, History, English, Kiswahili.\n• *High School (Form 5-6)*: Vitabu vya tahasusi (Combinations) zote.\n\n👉 *Marudio (Past Papers & Reviews)*: Andika */revision* kupata past papers za mitihani ya taifa.";

            case 'location':
                return "📍 *MAHALI TULIPO / OUR LOCATIONS*\n\nKaribu ututembelee katika matawi yetu yafuatayo:\n\n1. 🏢 *Tawi la Ubungo (Ubungo Branch)*\n   - Ubungo Plaza, Ghorofa ya Chini, karibu na kituo cha mabasi ya mwendo wa haraka ya Ubungo.\n\n2. 🏢 *Tawi la Kimara (Kimara Branch)*\n   - Kimara Mwisho, mkabala na kituo kikuu cha mabasi, jengo jipya la biashara la TRUMARK.\n\n⏱️ Tunaongeza urahisi wa manunuzi! Kama huwezi kufika dukani, andika */delivery* ili tukuletee ulipo!";

            case 'delivery':
                return "🚚 *HUDUMA YA USAFIRISHAJI / DELIVERY SERVICES*\n\nTunatuma vifaa na vitabu maeneo yote ya Tanzania:\n\n1. 🏙️ *Ndani ya Dar es Salaam*:\n   - Bodaboda/Bajaji inaleta mpaka ulipo (ndani ya masaa 2-4).\n   - Gharama: TZS 3,000 hadi TZS 5,000 (kulingana na umbali).\n\n2. 🚌 *Mikoani (Upcountry Delivery)*:\n   - Tunatuma kwa njia ya mabasi ya uhakika (Shabiby, Abood, Hood, BM, nk) au Courier Services (DHL, EMS).\n   - Gharama ya usafiri: Kuanzia TZS 5,000 (utachukua kwenye stendi ya basi mkoani kwako).\n\n🛒 *Jinsi ya Kuagiza*: Andika */order* sasa kuweka oda yako!";

            case 'printing':
                return "🖨️ *UCHAPISHAJI NA COPY / PRINTING & COPY SERVICES*\n\nTunatoa huduma bora na za haraka za uchapishaji:\n\n• *Black & White Printing/Photocopy*: TZS 100 kwa ukurasa mmoja (punguzo kubwa kwa kazi nyingi za shule/ofisi).\n• *Color Printing*: Kazi safi na zenye muonekano mzuri (kuanzia TZS 500).\n• *Document Binding*: Spiral binding na Hard binding kwa ripoti au thesis.\n• *Lamination*: Kulinda nyaraka zako muhimu dhidi ya maji na uchafu.\n• *Graphic Design*: Kudesign nembo, vipeperushi, business cards nk.\n\n👉 Tuma nyaraka zako (PDF au Word) kupitia WhatsApp hii, kisha andika */support* uongee na mchapishaji wetu!";

            case 'wholesale':
                return "📦 *MAUZO YA JUMLA / WHOLESALE ORDERS*\n\nJe, unamiliki shule, duka la vitabu, au unahitaji vifaa kwa ajili ya taasisi/mradi wako?\n\n• *Punguzo la Bei*: Tunatoa punguzo hadi *15% - 20%* kwa wanunuzi wa jumla na shule.\n• *Uwasilishaji*: Tunapeleka mzigo hadi shuleni au dukani kwako (kwa oda kubwa za Dar na mikoani).\n• *Uaminifu*: Bidhaa zote ni halisi na zina viwango vya juu.\n\n👉 Omba nukuu ya bei kwa kuandika */quotation* au wasiliana moja kwa moja na meneja mauzo wetu kwa kuandika */support*!";

            case 'quotation':
                return "📄 *NUKUU YA BEI / PROFORMA & QUOTATION*\n\nKupata Proforma Invoice au Quotation rasmi kwa ajili ya Shule au Kampuni yako, tafadhali tumia hatua hizi:\n\n1. Andika orodha ya vitabu/vifaa unavyohitaji na idadi yake (mfano: Daftari A4 Counter Book 3 Quire - Box 5).\n2. Tuma jina kamili la Shule/Taasisi na anwani (mfano: TRUMARK High School, S.L.P 123, Dar es Salaam).\n3. Tuma maelezo haya hapa, kisha andika */support* ili mhasibu wetu ayapokee na kukuandalia nukuu rasmi ndani ya muda mfupi!";
            
            // Info
            case 'hours':
                return "⏰ *MUDA WA KAZI / WORKING HOURS*\n\nTuko wazi kukuhudumia siku zote isipokuwa Jumapili:\n\n• 📅 *Jumatatu hadi Ijumaa*: 8:00 AM - 6:00 PM\n• 📅 *Jumamosi*: 8:00 AM - 5:00 PM\n• ❌ *Jumapili na Sikukuu*: Tumefunga (Lakini unaweza kuacha ujumbe na tutakujibu siku ya kazi inayofuata).\n\n📍 Tembelea matawi yetu ya Ubungo au Kimara. Andika */location* kuona anwani.";

            case 'payment':
                return "💳 *NJIA ZA MALIPO / PAYMENT METHODS*\n\nIli kurahisisha manunuzi yako, unaweza kulipia kupitia njia zifuatazo:\n\n1. 📱 *Lipa na M-Pesa (Till Number)*:\n   - Namba ya Till: *567890*\n   - Jina la Biashara: *TRUMARK CO. LTD*\n\n2. 📱 *Tigo Pesa / Airtel Money*:\n   - Tuma kwa namba: *0794 467 694* (Jina: TRUMARK Support)\n\n3. 🏦 *Benki (Bank Transfer)*:\n   - *CRDB Bank*: Acc: *0150248769300* (Jina: TRUMARK CO. LTD)\n   - *NMB Bank*: Acc: *2201004567890* (Jina: TRUMARK CO. LTD)\n\n⚠️ *Kumbuka*: Baada ya kufanya malipo, tafadhali tuma picha au ujumbe wa muamala hapa ili tuthibitishe na kuanza kuandaa mzigo wako!";

            case 'catalog':
                return "📑 *KATALOGI YA BIDHAA / PRODUCT CATALOG*\n\nTunaandaa katalogi ya kisasa yenye bidhaa na bei zetu zote za hivi karibuni. \n\nKwa sasa, tafadhali andika jina la kitabu au vifaa unavyohitaji hapa, na tutakupa picha na bei zake mara moja. Unaweza pia kuandika */pricing* kuona bei za vifaa maarufu au */support* kuongea na mhudumu wetu.";

            case 'trust':
                return "⭐ *KWANINI UCHAGUE TRUMARK? / WHY TRUMARK?*\n\nTRUMARK Co. LTD ni nembo inayoaminika Tanzania kwa zaidi ya miaka 5 kwa sababu:\n\n1. ✅ *Uhakika vya Bidhaa*: Vitabu vyote vinafuata mtaala rasmi wa serikali na vimeidhinishwa.\n2. 💰 *Bei Nafuu*: Bei zetu ni rafiki kwa wazazi, walimu na shule.\n3. ⚡ *Uharaka*: Huduma ya delivery ya haraka popote nchini Tanzania.\n4. 🤝 *Uaminifu*: Tunathamini wateja wetu na tunalinda ubora wa huduma zetu kila siku.";

            case 'pricing':
                return "💰 *BEI ZA BIDHAA MAARUFU / PRICE LIST*\n\nHapa kuna bei za baadhi ya vifaa vyetu maarufu (Mauzo ya Reja reja):\n\n• 📑 *Karatasi za Print (A4 Reams)*: TZS 11,500 hadi 13,000 (kulingana na chapa - Double A, PaperOne nk).\n• 📓 *Daftari za Counter (3 Quire)*: TZS 2,500 kila moja.\n• 📓 *Daftari za Counter (4 Quire)*: TZS 3,200 kila moja.\n• 🖊️ *Kalamu (Boksi la kalamu 50 - Bic/Speedo)*: TZS 8,000 hadi 10,000.\n• 📖 *Vitabu vya Mazoezi (Exercise Books - A5)*: TZS 500 kila kimoja.\n• 🗂️ *Faili za Ofisi (Box Files)*: TZS 3,500 hadi 5,000 kila moja.\n\n⚠️ *Kumbuka*: Bei za jumla (Wholesale) zina punguzo kubwa! Andika */wholesale* kujua zaidi.";
            
            // Edu & Products
            case 'stationery':
                return "✏️ *VIFAA VYA OFISI NA SHULE / STATIONERY*\n\nTuna vifaa vyote vya ofisi na shule vya ubora wa juu:\n\n• *Karatasi*: A4 Reams, A3, Karatasi za Rangi, Manila papers.\n• *Madaftari*: Counter books (1, 2, 3, 4 Quire), Exercise books, Sketchbooks, na Diaries.\n• *Vifaa vya Kuandika*: Kalamu za wino, penseli, markers, highlighters, chaki nk.\n• *Vifaa vya Ofisi*: Box files, staplers, punch machines, rulers, makasi, gundi na stampu.\n• *Mathematical Sets*: Seti za hesabu na calculators za kisayansi.\n\n👉 Andika bidhaa unayotaka ili tukufahamishe bei zake, au andika */order* ili kuweka oda ya vifaa vyako!";

            case 'revision':
                return "📖 *VITABU VYA MARUDIO NA PAST PAPERS / REVISION BOOKS*\n\nMsaidie mwanafunzi kufanya vizuri katika mitihani ya NECTA kwa kutumia vitabu vyetu vya marudio:\n\n• 🏫 *Darasa la 4 & 7 (Standard 4 & 7)*: Past papers zenye majibu ya masomo yote (Sayansi, Hesabu, Kiswahili, English, nk).\n• 🎒 *Form 2 & Form 4 (O-Level)*: Solved Past Papers za miaka 10 iliyopita, Miongozo ya kujibu maswali ya mitihani.\n• 🎓 *Form 6 (A-Level)*: Vitabu vya marudio vya masomo ya sayansi na sanaa kulingana na tahasusi (PCM, PCB, PGM, HGL, HKL, EGM, nk).\n\n👉 Andika somo au darasa unalotaka ili kupata maelezo na bei ya vitabu husika!";

            case 'subjects':
                return "🔬 *MASOMO TUNAYOYAHUDUMIA / SUBJECTS*\n\nTuna vitabu vya masomo yote ya shule:\n\n1. 🧮 *Hesabu & Sayansi*: Mathematics, Physics, Chemistry, Biology, Information Technology (ICT).\n2. 🌍 *Sanaa & Jamii*: Geography, History, Civics, General Studies.\n3. 🗣️ *Lugha (Languages)*: English, Kiswahili, French, Arabic.\n4. 💼 *Biashara*: Commerce, Bookkeeping, Economics.\n\n👉 Andika masomo unayotaka kununulia vitabu, au andika */support* uongee na mhudumu wetu.";

            case 'schoolpacks':
                return "🎒 *VIFURUSHI VYA SHULE / BACK-TO-SCHOOL PACKS*\n\nOkoa muda na fedha kwa kununua vifurushi vyetu vilivyoandaliwa tayari kwa ajili ya mwanafunzi wako:\n\n1. 🧸 *Kifurushi cha Nursery (TZS 15,000)*:\n   - Kalamu za rangi, daftari la kuchora, herufi, namba na penseli.\n\n2. ✏️ *Kifurushi cha Primary (TZS 35,000)*:\n   - Daftari 12, Kalamu 10, Penseli, Rula, Seti ya hesabu, Kifutio na cherezo.\n\n3. 📚 *Kifurushi cha Secondary (TZS 55,000)*:\n   - Daftari za Counter book 6, Kalamu 12, Seti ya Hesabu (Mathematical Set), Scientific Calculator, rula na box file.\n\n👉 *Jinsi ya kuagiza*: Taja kifurushi unachotaka, kisha andika */order* ili tukuletee mzigo popote ulipo!";
            
            // Customer Service
            case 'order':
                return "🛒 *JINSI YA KUFANYA ODA / HOW TO ORDER*\n\nKuweka oda yako kwa urahisi sana, fuata hatua hizi:\n\n1. **Tuma orodha** ya vitabu au vifaa unavyotaka (mfano: Vitabu vya Biology Form 1 & 2 - nakala 1 kila kimoja).\n2. **Taja eneo lako** unapoishi au unakotaka mzigo upelekwe (Dar es Salaam - Mbezi, au Mkoani - Dodoma, Arusha nk).\n3. **Tuma jina lako** na namba ya simu ya mpokeaji.\n\nBaada ya kutuma maelezo haya, andika */support* ili mhudumu wetu athibitishe gharama na kukupatia maelekezo ya malipo. Karibu sana TRUMARK! 😊";

            case 'track':
                return "🔍 *KUFUATILIA MZIGO / ORDER TRACKING*\n\nJe, tayari umeshafanya malipo na unataka kujua hatua ya mzigo wako?\n\n• *Ndani ya Dar es Salaam*: Mzigo unatumwa ndani ya masaa 2-4 baada ya malipo. Tutakupigia simu bodaboda/bajaji akiondoka.\n• *Mikoani*: Mara baada ya kukabidhi mzigo kwenye basi, tutakutumia **picha ya risiti (Waybill)** yenye namba ya simu ya dereva wa basi hapa WhatsApp.\n\n👉 Kama unahitaji msaada wowote kuhusu ufuatiliaji wa mzigo, andika tu */support* na tutakusaidia mara moja!";

            case 'help':
                return "🆘 *MAJELEKO / HELP MENU*\n\nTuna kila kitu unachohitaji! Andika neno lolote hapa, au tumia amri zifuatazo:\n\n📦 *Bidhaa & Huduma*:\n/products - Bidhaa zetu zote\n/books - Vitabu vya Shule\n/stationery - Vifaa vya Ofisi/Shule\n/printing - Huduma ya Printing/Copy\n/wholesale - Mauzo ya Jumla\n/revision - Vitabu vya Marudio/Mitihani\n/subjects - Masomo yote ya vitabu\n/schoolpacks - Vifurushi vya bei nafuu\n\n🚚 *Oda & Usafirishaji*:\n/order - Jinsi ya kufanya oda\n/delivery - Huduma ya kutuma mzigo\n/track - Kufuatilia mzigo wako\n/payment - Njia za kufanya malipo\n/pricing - Bei za bidhaa maarufu\n\n🏢 *Mawasiliano & Muda*:\n/location - Matawi yetu\n/hours - Muda wetu wa kazi\n/trust - Kuhusu TRUMARK\n/support - Ongea na Mhudumu wetu";

            case 'welcome':
                return "👋 *KARIBU TRUMARK CO. LTD! / WELCOME TO TRUMARK!*\n\nHabari! Sisi ni wauzaji wa vitabu vyote vya shule, vifaa vya ofisini/shuleni na watoaji wa huduma bora za printing na photocopy Tanzania. 😊\n\nAndika neno lolote hapa kuuliza swali, au chagua huduma unayohitaji kwa kuandika amri hizi:\n\n📦 *Bidhaa & Vifaa (Products & Catalog)*:\n👉 Andika */products* - Kuona bidhaa zetu zote.\n👉 Andika */books* - Kujua vitabu vya shule tunavyouza.\n👉 Andika */stationery* - Kuona vifaa vya ofisi na shule.\n👉 Andika */schoolpacks* - Vifurushi vya Back-to-School.\n\n🚚 *Oda & Malipo (Order & Delivery)*:\n👉 Andika */order* - Jinsi ya kuweka oda yako.\n👉 Andika */delivery* - Maelezo ya kutumiwa mzigo.\n👉 Andika */payment* - Njia za kufanya malipo na namba zetu.\n\n📍 *Ofisi & Mawasiliano*:\n👉 Andika */location* - Kupata ramani na matawi yetu Ubungo & Kimara.\n👉 Andika */support* - Ongea na Mhudumu wetu (Live Support).\n\nTRUMARK inakujali! Tunakutakia siku njema na manunuzi mema! 🌟";

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
        return "TRUMARK Customer Support 😊\n\n📞 Call / WhatsApp: 0794 467 694\n\nWe assist with:\n• Orders\n• Products\n• Delivery\n• Pricing\n• Printing services\n\nReply here for immediate help, or click to chat with a human directly: https://wa.me/255794467694";
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

        $systemPrompt = "You are TRUMARK Stationery & Books AI assistant. Respond in Swahili with simple English when needed. Be short, helpful, professional, and friendly. You handle school books, stationery, printing services, delivery, orders, and pricing. If the user is unclear, ask a follow-up question. Do not use formatting like bolding or italics excessively.";

        try {
            $response = \Illuminate\Support\Facades\Http::post("https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    // Simulate system context via conversation turns
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $systemPrompt . "\n\nCustomer message: " . $text]]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
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
}
