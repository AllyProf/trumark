@extends('layouts.vali')
@section('title', 'WhatsApp Live Chat')
@section('page_icon', 'fa-whatsapp')
@section('subtitle')Monitor conversations, reply manually, and control bot status@endsection

@section('styles')
<style>
.chat-wrap{display:flex;height:calc(100vh - 200px);min-height:550px;border:1px solid #ddd;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.06)}
.chat-left{width:320px;display:flex;flex-direction:column;border-right:1px solid #e0e0e0;background:#fdfdfd}
.chat-right{flex:1;display:flex;flex-direction:column;background:#efeae2}
.search-bar{padding:10px;border-bottom:1px solid #eee;background:#f5f5f5}
.search-bar input{border-radius:20px;font-size:13px;padding-left:14px}
.new-chat-btn{margin:8px 10px 0;border-radius:20px;font-size:12px;font-weight:bold;background:#940000;color:#fff;border:none;width:calc(100% - 20px);padding:7px}
.new-chat-btn:hover{background:#7a0000}
.thread-list{flex:1;overflow-y:auto}
.thread-item{display:flex;padding:12px 14px;border-bottom:1px solid #f2f2f2;cursor:pointer;align-items:center;transition:background .15s}
.thread-item:hover,.thread-item.active{background:#ebebeb}
.t-avatar{width:42px;height:42px;border-radius:50%;background:#940000;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:15px;flex-shrink:0;margin-right:10px;position:relative}
.t-info{flex:1;min-width:0}
.t-header{display:flex;justify-content:space-between;margin-bottom:3px}
.t-name{font-weight:bold;font-size:13px;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.t-time{font-size:10px;color:#999}
.t-row2{display:flex;justify-content:space-between;align-items:center}
.t-snip{font-size:11.5px;color:#666;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;margin-right:6px}
.badge-bot-active{background:#d4edda;color:#155724;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:bold;white-space:nowrap}
.badge-bot-paused{background:#f8d7da;color:#721c24;font-size:9px;padding:2px 6px;border-radius:4px;font-weight:bold;white-space:nowrap}
.unread-dot{width:18px;height:18px;background:#940000;color:#fff;border-radius:50%;font-size:9px;display:flex;align-items:center;justify-content:center;font-weight:bold;flex-shrink:0}
/* Chat right */
.chat-header{padding:12px 18px;background:#f0f2f5;border-bottom:1px solid #e0e0e0;display:flex;justify-content:space-between;align-items:center}
.chat-header-left{display:flex;align-items:center;gap:10px}
.h-name{font-weight:bold;font-size:14px;color:#333}
.h-sub{font-size:11px;color:#666}
.chat-body{flex:1;padding:16px;overflow-y:auto;display:flex;flex-direction:column;gap:10px}
.msg-wrap{display:flex;width:100%}
.msg-wrap.in{justify-content:flex-start}
.msg-wrap.out{justify-content:flex-end}
.bubble{max-width:65%;padding:8px 12px;border-radius:8px;font-size:13px;line-height:1.4;box-shadow:0 1px 1px rgba(0,0,0,.08);word-break:break-word}
.msg-wrap.in .bubble{background:#fff;border-top-left-radius:0}
.msg-wrap.out .bubble{background:#d9fdd3;border-top-right-radius:0}
.msg-wrap.out.bot .bubble{background:#e3f2fd;border:1px solid #bbdefb}
.msg-meta{font-size:9.5px;color:#999;text-align:right;margin-top:3px;display:flex;align-items:center;justify-content:flex-end;gap:3px}
.tick{font-size:12px}
.tick.sent{color:#aaa}
.tick.delivered{color:#aaa}
.tick.read{color:#53bdeb}
.tick.failed{color:#e74c3c}
.chat-footer{padding:10px 16px;background:#f0f2f5;border-top:1px solid #e0e0e0;display:flex;gap:8px;align-items:center}
.chat-input{flex:1;border:1px solid #ccc;border-radius:20px;padding:9px 16px;font-size:13px;outline:none}
.send-btn{width:40px;height:40px;border-radius:50%;background:#940000;color:#fff;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .2s;flex-shrink:0}
.send-btn:hover{background:#7a0000}
.empty-chat{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#999;text-align:center}
.empty-chat i{font-size:56px;color:#ccc;margin-bottom:12px}
.bot-btn{font-size:11px;font-weight:bold;border-radius:20px;padding:5px 14px;border:none;cursor:pointer;transition:all .2s}
.bot-on{background:#d4edda;color:#155724}
.bot-off{background:#f8d7da;color:#721c24}
.window-warn{background:#fff3cd;color:#856404;padding:8px 16px;font-size:12px;border-bottom:1px solid #ffc107;display:flex;align-items:center;gap:6px}
.d-none{display:none!important}
/* New thread green highlight */
@keyframes newThreadPulse{0%{background:#d4edda}50%{background:#c3e6cb}100%{background:transparent}}
.thread-item.is-new{animation:newThreadPulse 2s ease 3;border-left:3px solid #28a745;}
.thread-item.is-new .t-name{color:#155724 !important;font-weight:900 !important;}
.thread-item.is-new .t-avatar{background:#28a745 !important;}

/* Mobile & Tablet Responsiveness */
@media (max-width: 767.98px) {
  .chat-wrap {
    height: calc(100vh - 120px) !important;
    min-height: 480px !important;
  }
  .chat-left {
    width: 100% !important;
    border-right: none;
  }
  .chat-right {
    width: 100% !important;
    flex: 1;
  }
  /* Toggle panes based on thread-opened state */
  .chat-wrap:not(.thread-opened) .chat-right {
    display: none !important;
  }
  .chat-wrap.thread-opened .chat-left {
    display: none !important;
  }
  .chat-footer {
    padding: 8px 10px;
  }
  .bubble {
    max-width: 85%;
  }
}
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="chat-wrap">

      {{-- LEFT --}}
      <div class="chat-left">
        <button class="new-chat-btn" data-toggle="modal" data-target="#newChatModal">
          <i class="fa fa-plus mr-1"></i> Anzisha Chat Mpya
        </button>
        <div class="search-bar">
          <input type="text" id="searchInput" class="form-control" placeholder="Tafuta jina au namba...">
        </div>
        <div class="thread-list" id="threadList">
          @forelse($threads as $t)
            @php
              $dispName = $t->customer_name ?? $t->phone;
              $initials  = strtoupper(substr(collect(explode(' ',$dispName))->map(fn($w)=>$w[0]??'')->join(''),0,2));
            @endphp
            <div class="thread-item"
                 data-phone="{{ $t->phone }}"
                 data-name="{{ $dispName }}"
                 data-cid="{{ $t->customer_id ?? '' }}">
              <div class="t-avatar">{{ $initials }}</div>
              <div class="t-info">
                <div class="t-header">
                  <div class="t-name">{{ $dispName }}</div>
                  <div class="t-time">{{ \Carbon\Carbon::parse($t->created_at)->diffForHumans(null,true) }}</div>
                </div>
                <div class="t-row2">
                  <div class="t-snip">{{ Str::limit($t->message,45) }}</div>
                  <span class="badge-bot-{{ $t->is_bot_paused?'paused':'active' }}"
                        id="badge-{{ preg_replace('/[^0-9]/','', $t->phone) }}">
                    {{ $t->is_bot_paused?'Bot Paused':'Bot Active' }}
                  </span>
                  @if($t->unread_count > 0)
                    <span class="unread-dot ml-1">{{ $t->unread_count }}</span>
                  @endif
                </div>
              </div>
            </div>
          @empty
            <div class="text-center py-5 text-muted">
              <i class="fa fa-comments-o fa-3x d-block mb-2"></i>
              Hakuna mazungumzo bado.
            </div>
          @endforelse
        </div>
      </div>

      {{-- RIGHT --}}
      <div class="chat-right">
        <div class="empty-chat" id="emptyState">
          <i class="fa fa-whatsapp"></i>
          <h5>Trumark WhatsApp Live Chat</h5>
          <p>Chagua mazungumzo kushoto kuona ujumbe.</p>
        </div>

        <div class="chat-header d-none" id="chatHeader">
          <div class="chat-header-left">
            {{-- Back chevron for mobile view --}}
            <button id="backBtn" class="btn btn-light btn-sm mr-2 d-md-none" style="border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" onclick="goBackToList()">
              <i class="fa fa-chevron-left text-muted"></i>
            </button>
            <div class="t-avatar" id="hAvatar">WA</div>
            <div>
              <div class="h-name" id="hName">-</div>
              <div class="h-sub" id="hPhone">-</div>
            </div>
            <a href="#" id="crmLink" target="_blank" class="btn btn-outline-secondary btn-sm ml-2" style="font-size:11px;display:none">
              <i class="fa fa-user"></i> CRM Profile
            </a>
          </div>
          <button class="bot-btn bot-on" id="botBtn" onclick="toggleBot()">
            <i class="fa fa-android mr-1"></i> Bot: Active
          </button>
        </div>

        <div class="window-warn d-none" id="windowWarn">
          <i class="fa fa-clock-o"></i>
          <strong>24h Window Imefungwa:</strong> Mteja hajatuma ujumbe kwa zaidi ya masaa 24. Tumia template message tu.
        </div>

        <div class="chat-body d-none" id="chatBody"></div>

        <div class="chat-footer d-none" id="chatFooter">
          <input type="text" id="chatInput" class="chat-input" placeholder="Andika ujumbe..." onkeydown="if(event.key==='Enter')sendMsg()">
          <button class="send-btn" onclick="sendMsg()"><i class="fa fa-paper-plane"></i></button>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- NEW CHAT MODAL --}}
<div class="modal fade" id="newChatModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:#940000;color:#fff">
        <h5 class="modal-title"><i class="fa fa-whatsapp mr-1"></i> Anzisha Chat Mpya</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="font-weight-bold small">Tafuta Mteja (CRM) au Weka Namba</label>
          <input type="text" id="custSearch" class="form-control" placeholder="Andika jina au namba...">
          <div id="custResults" class="border rounded mt-1" style="max-height:180px;overflow-y:auto;display:none"></div>
        </div>
        <div class="form-group">
          <label class="font-weight-bold small">Namba ya WhatsApp (na nchi code, mfano: 255712345678)</label>
          <input type="text" id="newPhone" class="form-control" placeholder="255712345678">
        </div>
        <div class="form-group">
          <label class="font-weight-bold small">Ujumbe wa Kwanza</label>
          <textarea id="newMessage" class="form-control" rows="3" placeholder="Andika ujumbe..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Funga</button>
        <button class="btn btn-success btn-sm" onclick="startNewChat()">
          <i class="fa fa-paper-plane mr-1"></i> Tuma
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
var activePhone = null, pollMsgInterval = null, pollThreadInterval = null;
var lastMsgCount = 0, lastThreadSnapshot = '';

// ── Thread click ──────────────────────────────────────────────
document.querySelectorAll('.thread-item').forEach(el => {
  el.addEventListener('click', function() {
    openThread(this.dataset.phone, this.dataset.name, this.dataset.cid);
  });
});

function openThread(phone, name, cid) {
  activePhone = phone;
  document.querySelectorAll('.thread-item').forEach(e=>e.classList.remove('active'));
  let item = document.querySelector(`.thread-item[data-phone="${phone}"]`);
  if (item) item.classList.add('active');

  // Trigger mobile pane shift
  document.querySelector('.chat-wrap').classList.add('thread-opened');

  document.getElementById('emptyState').classList.add('d-none');
  ['chatHeader','chatBody','chatFooter'].forEach(id=>document.getElementById(id).classList.remove('d-none'));

  let initials = name.split(' ').map(n=>n[0]||'').join('').substring(0,2).toUpperCase();
  document.getElementById('hAvatar').textContent = initials;
  document.getElementById('hName').textContent   = name;
  document.getElementById('hPhone').textContent  = phone;

  let crmLink = document.getElementById('crmLink');
  if (cid) { crmLink.href = '/customers/'+cid; crmLink.style.display=''; }
  else { crmLink.style.display='none'; }

  lastMsgCount = 0;
  loadMessages(phone);

  // Mark incoming messages as read (clears unread counter)
  fetch(`/whatsapp/chat/mark-read/${encodeURIComponent(phone)}`,{
    method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}
  });
  // Clear unread dot in sidebar
  let item2 = document.querySelector(`.thread-item[data-phone="${phone}"]`);
  if(item2){ let dot=item2.querySelector('.unread-dot'); if(dot) dot.remove(); }

  if (pollMsgInterval) clearInterval(pollMsgInterval);
  pollMsgInterval = setInterval(()=>{ if(activePhone===phone) loadMessages(phone,true); }, 5000);
}

// ── Mobile Back Trigger ───────────────────────────────────────
function goBackToList() {
  document.querySelector('.chat-wrap').classList.remove('thread-opened');
  activePhone = null;
  if (pollMsgInterval) clearInterval(pollMsgInterval);
  document.querySelectorAll('.thread-item').forEach(e=>e.classList.remove('active'));
}

// ── Load messages ─────────────────────────────────────────────
function loadMessages(phone, silent=false) {
  let enc = encodeURIComponent(phone);
  fetch(`/whatsapp/chat/thread/${enc}`)
    .then(r=>r.json()).then(d=>{
      if(!d.success) return;
      renderMessages(d.messages);
      updateBotBtn(d.is_bot_paused);
      updateBadge(phone, d.is_bot_paused);

      // CRM link update
      if (d.customer) {
        let lnk=document.getElementById('crmLink');
        lnk.href='/customers/'+d.customer.id;
        lnk.style.display='';
      }

      // 24h window
      let warn=document.getElementById('windowWarn');
      warn.classList.toggle('d-none', d.window_open !== false);

      if (!silent || d.messages.length > lastMsgCount) {
        scrollBottom();
        if (silent && d.messages.length > lastMsgCount) flashTitle(d.messages.length - lastMsgCount);
      }
      lastMsgCount = d.messages.length;
    });
}

// ── Render bubbles ────────────────────────────────────────────
function renderMessages(msgs) {
  let body = document.getElementById('chatBody');
  body.innerHTML = '';
  msgs.forEach(m => {
    // incoming = received OR read_by_agent (already read by staff)
    let isIn  = m.status === 'received' || m.status === 'read_by_agent' || m.message.startsWith('INCOMING:');
    let cls   = isIn ? 'in' : 'out';
    let isBot = !isIn && m.message.startsWith('[BOT REPLY]');
    if (isBot) cls += ' bot';
    let text = m.message
      .replace(/^INCOMING:\s*/,'')
      .replace(/^\[BOT REPLY\]\s*/,'');
    let time = new Date(m.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    let tick = '';
    if (!isIn) {
      if      (m.status==='read')      tick='<span class="tick read">✓✓</span>';
      else if (m.status==='delivered') tick='<span class="tick delivered">✓✓</span>';
      else if (m.status==='sent')      tick='<span class="tick sent">✓</span>';
      else if (m.status==='failed')    tick='<span class="tick failed">⚠</span>';
    }
    body.insertAdjacentHTML('beforeend',`
      <div class="msg-wrap ${cls}">
        <div class="bubble">
          ${esc(text)}
          <div class="msg-meta"><span>${time}</span>${tick}</div>
        </div>
      </div>`);
  });
}

// ── Send manual message ───────────────────────────────────────
function sendMsg() {
  let inp = document.getElementById('chatInput');
  let txt = inp.value.trim();
  if (!txt || !activePhone) return;
  inp.disabled = true;
  fetch('/whatsapp/chat/send',{
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
    body: JSON.stringify({phone:activePhone, message:txt})
  }).then(r=>r.json()).then(d=>{
    inp.disabled=false;
    if(d.success){ inp.value=''; loadMessages(activePhone); }
    else alert('Hitilafu: '+d.message);
  }).catch(()=>{inp.disabled=false;alert('Tatizo la mtandao.');});
}

// ── Toggle bot ────────────────────────────────────────────────
function toggleBot() {
  if (!activePhone) return;
  fetch(`/whatsapp/chat/toggle-bot/${encodeURIComponent(activePhone)}`,{
    method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}
  }).then(r=>r.json()).then(d=>{
    if(d.success){ updateBotBtn(d.status==='paused'); updateBadge(activePhone,d.status==='paused'); }
  });
}

function updateBotBtn(paused) {
  let b=document.getElementById('botBtn');
  if(paused){ b.className='bot-btn bot-off'; b.innerHTML='<i class="fa fa-pause-circle mr-1"></i> Bot: Paused'; }
  else      { b.className='bot-btn bot-on';  b.innerHTML='<i class="fa fa-android mr-1"></i> Bot: Active'; }
}

function updateBadge(phone, paused) {
  let cl=phone.replace(/[^0-9]/g,'');
  let b=document.getElementById('badge-'+cl);
  if(!b) return;
  if(paused){ b.className='badge-bot-paused'; b.textContent='Bot Paused'; }
  else      { b.className='badge-bot-active'; b.textContent='Bot Active'; }
}

// ── Real-time thread list polling ─────────────────────────────
function pollThreads() {
  fetch('/whatsapp/chat/threads-data')
    .then(r=>r.json()).then(d=>{
      if(!d.success) return;
      // Detect changes by including unread_count in snapshot
      let snap = JSON.stringify(d.threads.map(t=>t.id+'|'+t.status+'|'+t.created_at+'|'+t.unread_count));
      if(snap === lastThreadSnapshot) return;
      lastThreadSnapshot = snap;
      refreshThreadList(d.threads);
    });
}
setInterval(pollThreads, 5000);

function refreshThreadList(threads) {
  let list = document.getElementById('threadList');
  threads.forEach(t => {
    let cl   = t.phone.replace(/[^0-9]/g,'');
    let name = t.customer_name || t.phone;
    let init = name.split(' ').map(n=>n[0]||'').join('').substring(0,2).toUpperCase();
    let existing = list.querySelector(`.thread-item[data-phone="${t.phone}"]`);
    let isActive  = activePhone === t.phone;
    let botClass  = t.is_bot_paused ? 'badge-bot-paused':'badge-bot-active';
    let botLabel  = t.is_bot_paused ? 'Bot Paused':'Bot Active';
    let unreadHtml= t.unread_count>0?`<span class="unread-dot ml-1">${t.unread_count}</span>`:'';
    let html = `
      <div class="thread-item${isActive?' active':''}" data-phone="${t.phone}" data-name="${name}" data-cid="${t.customer_id||''}">
        <div class="t-avatar">${init}</div>
        <div class="t-info">
          <div class="t-header">
            <div class="t-name">${name}</div>
            <div class="t-time">${timeAgo(t.created_at)}</div>
          </div>
          <div class="t-row2">
            <div class="t-snip">${t.message.substring(0,45)}</div>
            <span class="${botClass}" id="badge-${cl}">${botLabel}</span>
            ${unreadHtml}
          </div>
        </div>
      </div>`;
    if (existing) {
      existing.outerHTML = html;
    } else {
      // Brand new thread — insert at top with green highlight
      list.insertAdjacentHTML('afterbegin', html);
      let newEl2 = list.querySelector(`.thread-item[data-phone="${t.phone}"]`);
      if(newEl2) newEl2.classList.add('is-new');
      flashTitle(1);
    }
    // Re-bind click for new/replaced item
    let newEl = list.querySelector(`.thread-item[data-phone="${t.phone}"]`);
    if(newEl) newEl.addEventListener('click', function(){
      openThread(this.dataset.phone, this.dataset.name, this.dataset.cid);
    });
  });
}

// ── New Chat Modal ────────────────────────────────────────────
let custSearchTimer = null;
document.getElementById('custSearch').addEventListener('input', function(){
  clearTimeout(custSearchTimer);
  let q=this.value.trim();
  if(q.length<2){ document.getElementById('custResults').style.display='none'; return; }
  custSearchTimer = setTimeout(()=>{
    fetch(`/whatsapp/chat/search-customers?q=${encodeURIComponent(q)}`)
      .then(r=>r.json()).then(list=>{
        let box=document.getElementById('custResults');
        if(!list.length){ box.style.display='none'; return; }
        box.innerHTML = list.map(c=>`<div class="p-2 border-bottom" style="cursor:pointer;font-size:13px"
          onclick="selectCust('${c.phone}','${c.name.replace(/'/g,"\\'")}')">
          <strong>${c.name}</strong> &nbsp;<span class="text-muted">${c.phone}</span></div>`).join('');
        box.style.display='block';
      });
  }, 300);
});

function selectCust(phone, name) {
  document.getElementById('newPhone').value = phone;
  document.getElementById('custSearch').value = name;
  document.getElementById('custResults').style.display='none';
}

function startNewChat() {
  let phone = document.getElementById('newPhone').value.trim();
  let msg   = document.getElementById('newMessage').value.trim();
  if(!phone||!msg){alert('Weka namba na ujumbe.');return;}
  fetch('/whatsapp/chat/send',{
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
    body:JSON.stringify({phone:phone,message:msg})
  }).then(r=>r.json()).then(d=>{
    if(d.success){
      $('#newChatModal').modal('hide');
      document.getElementById('newPhone').value='';
      document.getElementById('newMessage').value='';
      document.getElementById('custSearch').value='';
      openThread(phone, phone, '');
    } else alert('Hitilafu: '+d.message);
  });
}

// ── Search filter ─────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function(){
  let q=this.value.toLowerCase();
  document.querySelectorAll('.thread-item').forEach(el=>{
    let match=el.dataset.name.toLowerCase().includes(q)||el.dataset.phone.includes(q);
    el.style.display=match?'':'none';
  });
});

// ── Helpers ───────────────────────────────────────────────────
function scrollBottom(){let b=document.getElementById('chatBody');b.scrollTop=b.scrollHeight;}
function esc(t){return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');}
function timeAgo(ts){
  let s=Math.floor((Date.now()-new Date(ts))/1000);
  if(s<60)return s+'s'; if(s<3600)return Math.floor(s/60)+'m'; if(s<86400)return Math.floor(s/3600)+'h';
  return Math.floor(s/86400)+'d';
}
let origTitle=document.title, flashTimer=null, flashCount=0;
function flashTitle(n){
  if(flashTimer)clearInterval(flashTimer);
  flashCount=0;
  flashTimer=setInterval(()=>{
    document.title = flashCount%2===0?`💬 (${n}) Ujumbe Mpya!`:origTitle;
    if(++flashCount>8){clearInterval(flashTimer);document.title=origTitle;}
  },700);
}
</script>
@endsection
