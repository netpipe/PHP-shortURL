<?php
declare(strict_types=1);
/*
+---------------------------------------------------------------------------+
|  RELAY — a single-file URL shortener  (PHP 8.x + SQLite, no dependencies)  |
|-----------------------------------------------------------------------------|
|  1. drop this file in  /shorturl/index.php                                   |
|  2. open  https://testsite.com/shorturl/  → the DB creates itself on first run|
|  3. short links look like    testsite.com/shorturl/?slug=x123x                |
+---------------------------------------------------------------------------+
*/

const CFG = [
    'db'          => __DIR__ . '/relay.sqlite',   // keep this OUTSIDE the webroot if you can
    'base_url'    => '',                          // '' = auto-detect, or 'https://testsite.com/shorturl/'
    'slug_len'    => 5,                           // random slug length
    'default_ttl' => 365,                         // days — the "typical" lifetime (1 year)
    'admin_pass'  => '',                          // '' = ledger open to anyone who finds the page.
                                                   // plain text OR a password_hash() string
    'log_clicks'  => true,                        // per-day history, powers the sparklines
    'reserved'    => ['admin','api','index','login','logout','export','json','static',
                      'assets','favicon','robots','health','slug','action','remove'],
];

const TTLS = ['1' => '1 day', '7' => '1 week', '30' => '1 month',
              '90' => '3 months', '365' => '1 year', '0' => 'custom…'];
/* ═══════════════════════════════ CSS ══════════════════════════════════════ */

define('CSS', <<<'CSS'
*{box-sizing:border-box}
:root{
  --ink:#04101b; --panel:#0a1e31; --panel2:#0e2a44; --line:#1c4569; --line2:#2b5c85;
  --text:#dcebf6; --fog:#8cb1ca; --paper:#e7eef4; --navy:#0a1b2b;
  --amber:#ffb02e; --mint:#3ee0a0; --coral:#ff6a52; --sky:#5ab6ff;
  --display:'Syne',system-ui,sans-serif; --body:'IBM Plex Sans',system-ui,sans-serif;
  --mono:'IBM Plex Mono',ui-monospace,SFMono-Regular,monospace;
}
html{scroll-behavior:smooth}
body{margin:0;background:var(--ink);color:var(--text);font:400 16px/1.6 var(--body);
  overflow-x:hidden;-webkit-font-smoothing:antialiased}
img,svg{display:block;max-width:100%}
a{color:inherit;text-decoration:none}
.mono{font-family:var(--mono);font-variant-ligatures:none}
.dim{color:var(--fog)}
.tiny{font-size:.74rem;line-height:1.5}
.struck{text-decoration:line-through;text-decoration-color:var(--coral)}
.amber-text{color:var(--amber)}
.ta-r{text-align:right}
.sep{opacity:.35;margin:0 .5rem}
.on{color:var(--amber)}

/* ---------- ambient background ---------- */
.bg{position:fixed;inset:0;z-index:-1;overflow:hidden;pointer-events:none}
.bg .grid{position:absolute;inset:-25%;
  background-image:linear-gradient(rgba(90,182,255,.075) 1px,transparent 1px),
                   linear-gradient(90deg,rgba(90,182,255,.075) 1px,transparent 1px),
                   linear-gradient(rgba(90,182,255,.16) 1px,transparent 1px),
                   linear-gradient(90deg,rgba(90,182,255,.16) 1px,transparent 1px);
  background-size:32px 32px,32px 32px,160px 160px,160px 160px;
  -webkit-mask-image:radial-gradient(120% 85% at 18% -5%,#000 15%,transparent 72%);
          mask-image:radial-gradient(120% 85% at 18% -5%,#000 15%,transparent 72%);
  animation:drift 90s linear infinite}
@keyframes drift{to{transform:translate3d(160px,160px,0)}}
.bg .wash{position:absolute;inset:0;
  background:radial-gradient(70% 55% at 88% -8%,rgba(255,176,46,.13),transparent 62%),
             radial-gradient(60% 55% at -5% 105%,rgba(62,224,160,.10),transparent 60%),
             linear-gradient(178deg,#08203500 0%,#04101b 62%,#02090f 100%)}
.bg .grain{position:absolute;inset:0;opacity:.28;mix-blend-mode:overlay;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)' opacity='.5'/%3E%3C/svg%3E")}
.wires{position:absolute;inset:0;width:100%;height:100%;opacity:.55}
.wires .w{fill:none;stroke:rgba(90,182,255,.22);stroke-width:1.2;stroke-dasharray:5 9;
  animation:march 4s linear infinite}
.wires .w2{stroke:rgba(255,176,46,.18);animation-duration:6s}
.wires .w3{stroke:rgba(62,224,160,.16);animation-duration:8s}
@keyframes march{to{stroke-dashoffset:-56}}
.pkt{fill:var(--amber);filter:drop-shadow(0 0 8px rgba(255,176,46,.85))}
.pkt.p2{fill:var(--mint);filter:drop-shadow(0 0 8px rgba(62,224,160,.8))}
.pkt.p3{fill:var(--sky);filter:drop-shadow(0 0 8px rgba(90,182,255,.8))}
@media (prefers-reduced-motion:reduce){*{animation-duration:.001s!important;transition-duration:.001s!important}}

/* ---------- chrome ---------- */
.topbar{position:sticky;top:0;z-index:40;display:flex;align-items:center;justify-content:space-between;
  gap:1rem;padding:.85rem clamp(1rem,4vw,3rem);background:rgba(4,16,27,.82);
  backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}
.brand{display:flex;align-items:baseline;gap:.7rem}
.mark{display:inline-grid;grid-template-columns:repeat(3,4px);gap:2px;align-self:center}
.mark i{width:4px;height:14px;background:var(--amber);display:block;animation:mk 2.4s ease-in-out infinite}
.mark i:nth-child(2){background:var(--mint);animation-delay:.2s}
.mark i:nth-child(3){background:var(--sky);animation-delay:.4s}
@keyframes mk{0%,100%{transform:scaleY(.45);opacity:.6}50%{transform:scaleY(1);opacity:1}}
.wm{font:800 1.18rem/1 var(--display);letter-spacing:.16em}
.tag{font:500 .66rem/1 var(--mono);color:var(--fog);letter-spacing:.1em;text-transform:uppercase}
.dotsep{opacity:.4;margin:0 .25rem}
.crumbs{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;color:var(--fog)}
.crumbs a:hover{color:var(--amber)}
main{padding:clamp(1.4rem,3.5vw,3rem) clamp(1rem,4vw,3rem) 4rem;max-width:1400px;margin:0 auto}
.sitefoot{display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:center;
  padding:1.4rem clamp(1rem,4vw,3rem);border-top:1px solid var(--line);color:var(--fog);
  font-size:.74rem;letter-spacing:.06em;max-width:1400px;margin:0 auto}
.sitefoot .mono{color:var(--amber);opacity:.8}

/* ---------- type ---------- */
.eyebrow{font:600 .7rem/1 var(--mono);letter-spacing:.28em;text-transform:uppercase;color:var(--amber);margin:0 0 .9rem}
.blink{animation:bl 1.1s steps(2) infinite}
@keyframes bl{50%{opacity:0}}
.display{font:800 clamp(2.4rem,6.2vw,4.5rem)/.9 var(--display);letter-spacing:-.025em;
  text-transform:uppercase;margin:0 0 1.1rem}
.display.sm{font-size:clamp(1.9rem,4.4vw,3rem)}
.stroke{color:transparent;-webkit-text-stroke:1.6px var(--fog);transition:-webkit-text-stroke-color .4s}
.forge:hover .stroke,.solo:hover .stroke{-webkit-text-stroke-color:var(--amber)}
.lede{max-width:52ch;color:#b6cee0;font-size:1.02rem;margin:0 0 1.8rem}
h2{font:800 clamp(1.5rem,3vw,2.3rem)/1 var(--display);letter-spacing:-.02em;text-transform:uppercase;margin:0}

/* ---------- panels ---------- */
.panel{position:relative;background:linear-gradient(170deg,rgba(14,42,68,.92),rgba(8,26,43,.94));
  border:1px solid var(--line);padding:1.15rem 1.25rem}
.panel::before,.panel::after{content:'';position:absolute;width:9px;height:9px;border:1px solid var(--amber);opacity:.6}
.panel::before{top:-1px;left:-1px;border-right:0;border-bottom:0}
.panel::after{bottom:-1px;right:-1px;border-left:0;border-top:0}
.phd{display:flex;justify-content:space-between;align-items:center;gap:.6rem;
  font:600 .68rem/1 var(--mono);letter-spacing:.2em;text-transform:uppercase;color:var(--fog);
  padding-bottom:.7rem;margin-bottom:.85rem;border-bottom:1px dashed var(--line)}
.pulse{width:7px;height:7px;background:var(--mint);border-radius:50%;box-shadow:0 0 0 0 rgba(62,224,160,.6);animation:pl 2s infinite}
.pulse.amber{background:var(--amber);box-shadow:0 0 0 0 rgba(255,176,46,.6)}
@keyframes pl{70%{box-shadow:0 0 0 9px rgba(62,224,160,0)}100%{box-shadow:0 0 0 0 rgba(62,224,160,0)}}

/* ---------- buttons ---------- */
.btn{display:inline-flex;align-items:center;gap:.55rem;font:600 .74rem/1 var(--mono);letter-spacing:.13em;
  text-transform:uppercase;padding:.85rem 1.15rem;border:1px solid var(--line2);background:var(--panel2);
  color:var(--text);cursor:pointer;transition:transform .12s,background .2s,border-color .2s,color .2s,box-shadow .2s}
.btn:hover{border-color:var(--amber);color:#fff;background:#14375a}
.btn:active{transform:translateY(1px)}
.btn.lg{padding:1.05rem 1.7rem;font-size:.82rem}
.btn.wide{width:100%;justify-content:center}
.btn.primary{background:var(--amber);color:#14202c;border-color:#c88400;box-shadow:0 5px 0 #96650a}
.btn.primary:hover{background:#ffbe54;color:#14202c}
.btn.primary:active{transform:translateY(5px);box-shadow:0 0 0 #96650a}
.btn.ghost{background:transparent;border-color:var(--line)}
.btn.ghost-out{background:transparent;color:var(--navy);border-color:rgba(10,27,43,.28)}
.btn.ghost-out:hover{background:rgba(10,27,43,.07);color:var(--navy);border-color:var(--navy)}
.btn.danger{background:var(--coral);color:#2a0c06;border-color:#c1412c;box-shadow:0 5px 0 #93301e}
.btn.danger:hover{background:#ff846e;color:#2a0c06}
.btn.danger:active{transform:translateY(5px);box-shadow:0 0 0 #93301e}
.btn.danger-out{background:transparent;border-color:rgba(255,106,82,.5);color:#ffb2a4}
.btn.danger-out:hover{background:rgba(255,106,82,.12);border-color:var(--coral);color:#fff}
.btn.tiny{padding:.55rem .8rem;font-size:.66rem}
.btn.done,.btn.done .bt{color:var(--mint)!important;border-color:var(--mint)!important}
.btn.primary.done{background:var(--mint);color:#062017!important;border-color:#1f9c6c!important}
.arrow{display:inline-block;transition:transform .25s cubic-bezier(.2,.8,.2,1)}
.btn:hover .arrow,a:hover .arrow{transform:translateX(4px)}
.rowbtns{display:flex;flex-wrap:wrap;gap:.6rem;align-items:center;margin-top:1rem}
.inline{display:inline}
.mini{font:600 .62rem/1 var(--mono);letter-spacing:.12em;text-transform:uppercase;background:none;
  border:1px solid rgba(10,27,43,.2);color:var(--navy);padding:.35rem .5rem;cursor:pointer;transition:.18s}
.mini:hover{background:var(--navy);color:var(--paper)}
.mini.danger{border-color:rgba(193,65,44,.4);color:#a5301e}
.mini.danger:hover{background:#c1412c;color:#fff}
.mini.done{border-color:var(--mint);background:#0f8a5c;color:#fff}
kbd{font:600 .68rem/1 var(--mono);border:1px solid var(--line2);padding:.15rem .35rem;color:var(--amber)}

/* ---------- bands ---------- */
.band{position:relative;border:1px solid var(--line);border-left:3px solid var(--amber);
  background:linear-gradient(100deg,rgba(14,42,68,.95),rgba(6,20,34,.9));padding:1.5rem clamp(1rem,3vw,2rem);margin-bottom:1.6rem}
.band.err{border-left-color:var(--coral);background:linear-gradient(100deg,rgba(60,20,14,.7),rgba(20,8,6,.35))}
.band.err p{color:#ffc4b8}
.band.note{border-left-color:var(--mint)}
.band-k{position:absolute;top:-.62rem;left:1rem;font:600 .6rem/1 var(--mono);letter-spacing:.24em;
  text-transform:uppercase;background:var(--ink);color:var(--amber);padding:.25rem .5rem;border:1px solid var(--line)}
.band.err .band-k{color:var(--coral)}
.band.note .band-k{color:var(--mint)}
.band p{margin:0}
.stamp{position:absolute;top:1rem;right:-.35rem;font:800 .78rem/1 var(--display);letter-spacing:.24em;
  text-transform:uppercase;color:var(--mint);border:2px solid var(--mint);padding:.4rem .9rem;
  transform:rotate(-6deg);opacity:.85;animation:stampin .5s cubic-bezier(.2,1.5,.4,1) both}
.stamp.grey{color:var(--coral);border-color:var(--coral)}
@keyframes stampin{from{transform:rotate(-6deg) scale(1.9);opacity:0}}
.bigurl{font:500 clamp(1rem,2.5vw,1.75rem)/1.35 var(--mono);word-break:break-all;color:var(--fog);
  cursor:copy;background:rgba(2,10,17,.55);border:1px dashed var(--line2);padding:.9rem 1rem;margin:.2rem 0 .2rem}
.bigurl b{color:var(--amber);font-weight:600}
.bigurl .q{color:var(--sky)}
.bigurl .sch{opacity:.5}
.meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1rem 1.6rem;margin:1.4rem 0 0;
  padding-top:1.2rem;border-top:1px solid var(--line)}
.meta.tight{margin-top:1rem;padding-top:.9rem}
.meta dt{font:600 .62rem/1 var(--mono);letter-spacing:.2em;text-transform:uppercase;color:var(--fog);margin-bottom:.4rem}
.meta dd{margin:0;font-size:.95rem;word-break:break-word}
.killbox{margin-top:1.5rem;border:1px solid rgba(255,106,82,.35);background:rgba(58,17,10,.35);padding:1.1rem 1.2rem}
.kill-hd{display:flex;flex-wrap:wrap;align-items:center;gap:.55rem;font:600 .74rem/1.2 var(--mono);
  letter-spacing:.14em;text-transform:uppercase;color:#ffb2a4}
.kill-hd .key{font-size:1rem}
.kill-hd em{font:400 .74rem/1.4 var(--body);letter-spacing:0;text-transform:none;color:var(--fog);flex:1 1 240px}
.killurl{display:block;margin:.8rem 0 0;padding:.8rem .9rem;background:rgba(2,10,17,.6);
  border:1px dashed rgba(255,106,82,.4);color:#ffd5cc;font-size:.8rem;word-break:break-all;cursor:copy}

/* ---------- forge ---------- */
.forge{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(300px,.85fr);gap:clamp(1.4rem,3vw,3rem);
  align-items:start;padding-top:.5rem}
.forge-r{position:sticky;top:5rem;display:grid;gap:1.2rem}
@media(max-width:940px){.forge{grid-template-columns:1fr}.forge-r{position:static}}
.form{display:grid;grid-template-columns:1fr 1fr;gap:1.15rem;max-width:760px}
.form .span2{grid-column:1/-1}
@media(max-width:620px){.form{grid-template-columns:1fr}.grid2{grid-template-columns:1fr!important}}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:1.15rem}
.field{display:grid;gap:.45rem;min-width:0}
.flabel{font:600 .66rem/1 var(--mono);letter-spacing:.2em;text-transform:uppercase;color:var(--fog);transition:color .2s}
.flabel em{font-style:normal;opacity:.5;letter-spacing:.1em;margin-left:.3rem}
.field:focus-within .flabel{color:var(--amber)}
input[type=text],input[type=password],input[type=number],select{width:100%;font:400 .95rem/1.4 var(--mono);
  color:var(--text);background:#03101a;border:1px solid var(--line);padding:.85rem .9rem;
  transition:border-color .2s,box-shadow .2s,background .2s;border-radius:0}
input::placeholder{color:#436a89}
input:focus,select:focus{outline:none;border-color:var(--sky);background:#061a29;
  box-shadow:0 0 0 3px rgba(90,182,255,.16),inset 0 0 30px rgba(90,182,255,.05)}
select{appearance:none;background-image:linear-gradient(45deg,transparent 50%,var(--amber) 50%),
  linear-gradient(135deg,var(--amber) 50%,transparent 50%);
  background-position:calc(100% - 16px) 55%,calc(100% - 11px) 55%;
  background-size:5px 5px,5px 5px;background-repeat:no-repeat;cursor:pointer}
select:disabled{opacity:.4}
.fhint{font-size:.72rem;color:var(--fog)}
.fhint code{font-family:var(--mono);color:var(--mint)}
.with-btn{display:flex;gap:.4rem}
.with-btn input{flex:1;min-width:0}
.with-btn.narrow{max-width:200px}
.unit{display:flex;align-items:center;padding:0 .7rem;font:600 .68rem/1 var(--mono);
  letter-spacing:.14em;text-transform:uppercase;color:var(--fog);border:1px solid var(--line);border-left:0}
.dice{width:44px;border:1px solid var(--line);background:#03101a;color:var(--amber);font-size:1.15rem;
  cursor:pointer;transition:.2s}
.dice:hover{border-color:var(--amber);background:#123049;transform:rotate(-12deg)}
.dice:active{transform:rotate(12deg) scale(.94)}
.switches{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:620px){.switches{grid-template-columns:1fr}}
.sw{display:flex;align-items:center;gap:.8rem;cursor:pointer;border:1px solid var(--line);
  padding:.8rem .9rem;background:rgba(3,16,26,.5);transition:border-color .2s,background .2s}
.sw:hover{border-color:var(--line2);background:rgba(10,32,52,.7)}
.sw input{position:absolute;opacity:0;pointer-events:none}
.track{flex:0 0 auto;width:38px;height:20px;border:1px solid var(--line2);background:#02151f;position:relative;transition:.25s}
.knob{position:absolute;top:2px;left:2px;width:14px;height:14px;background:var(--fog);transition:.25s cubic-bezier(.4,1.6,.5,1)}
.sw input:checked+.track{border-color:var(--mint);background:rgba(62,224,160,.14)}
.sw input:checked+.track .knob{left:20px;background:var(--mint);box-shadow:0 0 10px rgba(62,224,160,.8)}
.sw input:focus-visible+.track{box-shadow:0 0 0 3px rgba(90,182,255,.25)}
.swtxt{font:600 .8rem/1.2 var(--mono);letter-spacing:.06em;text-transform:uppercase;display:grid;gap:.15rem}
.swtxt small{font:400 .68rem/1.3 var(--body);letter-spacing:0;text-transform:none;color:var(--fog)}
.pw{overflow:hidden;animation:slidein .3s ease both}
@keyframes slidein{from{opacity:0;transform:translateY(-8px)}}
.actions{display:flex;flex-wrap:wrap;align-items:center;gap:1rem;margin-top:.4rem}
.hint{font-size:.72rem;color:var(--fog)}
.row-gap{margin-top:1.2rem}

/* ---------- preview ---------- */
.pv-url{font:500 .95rem/1.5 var(--mono);word-break:break-all;background:rgba(2,10,17,.6);
  border:1px dashed var(--line2);padding:.9rem;cursor:copy}
.pv-url .pv-path{color:var(--fog)}
.pv-url .q{color:var(--sky)}
.pv-url b{color:var(--amber);font-weight:600;transition:color .2s}
.pv-url b.roll{animation:roll .35s ease}
@keyframes roll{50%{color:#fff;text-shadow:0 0 14px rgba(255,176,46,.9)}}
.pv-list{list-style:none;margin:1rem 0 0;padding:0;display:grid;gap:.5rem}
.pv-list li{display:flex;justify-content:space-between;gap:1rem;font-size:.8rem;
  border-bottom:1px dotted rgba(28,69,105,.7);padding-bottom:.4rem}
.pv-list span{font:600 .62rem/1.4 var(--mono);letter-spacing:.18em;text-transform:uppercase;color:var(--fog)}
.pv-list b{font-weight:500;color:var(--text);text-align:right}
.pv-foot{margin:1rem 0 0;font-size:.74rem;color:var(--fog);line-height:1.55}
.pv-foot b{color:var(--coral)}
.hopsvg{width:100%;height:auto;margin-top:.3rem}
.hopline{stroke:var(--line2);stroke-width:1.5;stroke-dasharray:4 7;animation:march 3s linear infinite}
.node rect,.node.end rect{fill:none;stroke:var(--amber);stroke-width:1.5}
.node.end rect{stroke:var(--mint)}
.hop-pkt{fill:var(--amber);filter:drop-shadow(0 0 6px rgba(255,176,46,.9))}
.hop-lbl{display:flex;justify-content:space-between;font:600 .6rem/1 var(--mono);
  letter-spacing:.14em;text-transform:uppercase;color:var(--fog);margin-top:.4rem}

/* ---------- manifest ---------- */
.manifest{margin-top:clamp(3rem,7vw,6rem)}
.mf-hd{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1.2rem;
  padding-bottom:1.1rem;border-bottom:1px solid var(--line);margin-bottom:1.6rem}
.mf-hd h2{color:var(--text)}
.mf-tools{display:flex;gap:.5rem;flex-wrap:wrap}
.mf-body{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:1.6rem;align-items:start}
@media(max-width:1080px){.mf-body{grid-template-columns:1fr}.mf-rail{grid-template-columns:repeat(auto-fit,minmax(240px,1fr));display:grid;gap:1.2rem}}
.tablewrap{background:var(--paper);color:var(--navy);overflow-x:auto;position:relative;
  background-image:repeating-linear-gradient(180deg,transparent 0 43px,rgba(10,27,43,.045) 43px 44px)}
table.ledger{width:100%;border-collapse:collapse;min-width:840px;font-size:.86rem}
table.ledger th{font:600 .6rem/1 var(--mono);letter-spacing:.2em;text-transform:uppercase;
  text-align:left;padding:.9rem .8rem;border-bottom:2px solid rgba(10,27,43,.8);color:var(--navy);white-space:nowrap}
.th-sub{opacity:.45;letter-spacing:.1em}
table.ledger td{padding:.75rem .8rem;border-bottom:1px solid rgba(10,27,43,.12);vertical-align:middle}
table.ledger tbody tr{transition:background .18s,box-shadow .18s;position:relative}
table.ledger tbody tr:hover{background:#fff;box-shadow:inset 3px 0 0 var(--amber)}
tr.is-dead td{opacity:.5}
tr.is-dead:hover td{opacity:.85}
.slugline{display:flex;align-items:center;gap:.4rem;font:600 1rem/1 var(--mono);color:var(--navy)}
.slugline b{letter-spacing:.02em;transition:color .18s}
.slugline:hover b{color:#c1412c}
.slugline .arrow{color:var(--fog);font-size:.8rem}
.dest{display:block;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#274a63}
.dest .h{font-weight:600;color:var(--navy)}
.dest:hover{text-decoration:underline;text-decoration-color:var(--coral)}
.clicks{font-weight:600;font-size:1rem;margin-right:.5rem}
.spark{display:inline-block;width:88px;height:20px;vertical-align:-4px}
.spark rect{fill:var(--navy)}
.spark rect.zero{opacity:.16}
.spark rect.on{opacity:.55}
.spark rect.peak{opacity:1;fill:#c1412c}
.pill{display:inline-block;font:600 .62rem/1 var(--mono);letter-spacing:.14em;text-transform:uppercase;
  padding:.35rem .5rem;border:1px solid currentColor;white-space:nowrap}
.pill.sm{padding:.25rem .4rem;font-size:.56rem}
.pill.mint{color:#0f8a5c}.pill.amber{color:#9a6a00}.pill.coral{color:#c1412c}
.pill.sky{color:#1a6fa8}.pill.fog{color:#5c7d94}
.flags{display:flex;gap:.3rem;flex-wrap:wrap}
.empty td{padding:0}
.emptyspace{display:flex;align-items:center;gap:1rem;padding:2.5rem 1.5rem;color:#5c7d94}
.emptyspace span{font-size:2rem;color:var(--amber);animation:mk 2.4s ease-in-out infinite}
.emptyspace p{margin:0;font:400 .9rem/1.5 var(--body)}
.mf-rail{display:grid;gap:1.2rem;align-content:start}
.stat ul,.soon ul{list-style:none;margin:0;padding:0;display:grid;gap:.7rem}
.stat li{display:flex;align-items:baseline;gap:.6rem;border-bottom:1px dotted rgba(28,69,105,.8);padding-bottom:.5rem}
.stat b{font-size:1.5rem;font-weight:500;color:var(--amber);min-width:2.6ch}
.stat span{font-size:.74rem;color:var(--fog);margin-left:auto;text-align:right}
.rank ol{list-style:none;margin:0;padding:0;display:grid;gap:.55rem}
.rank li{display:grid;grid-template-columns:1.4rem 1fr 60px 2.4rem;align-items:center;gap:.5rem;font-size:.78rem}
.rank .rk{color:var(--fog);font-size:.66rem}
.rank a{color:var(--amber);font-size:.8rem}
.rank a:hover{text-decoration:underline}
.rank .bar{height:5px;background:rgba(90,182,255,.14);overflow:hidden}
.rank .bar i{display:block;height:100%;width:var(--w);background:linear-gradient(90deg,var(--mint),var(--sky));
  transform-origin:left;animation:grow .9s cubic-bezier(.2,.8,.2,1) both}
@keyframes grow{from{transform:scaleX(0)}}
.rank b{text-align:right;color:var(--text)}
.soon li{display:flex;justify-content:space-between;gap:.6rem;font-size:.78rem;
  border-bottom:1px dotted rgba(28,69,105,.8);padding-bottom:.45rem}
.soon a{color:var(--coral)}
.soon span{color:var(--fog);font-family:var(--mono);font-size:.72rem}

/* ---------- solo pages ---------- */
.solo{display:grid;place-items:center;padding:clamp(1rem,6vw,4rem) 0}
.solo-p{max-width:560px;width:100%;padding:clamp(1.6rem,4vw,2.6rem)}
.danger-p{border-color:rgba(255,106,82,.4)}
.lockmark{font-size:1.6rem;color:var(--mint);margin-bottom:1rem;position:relative;display:inline-block}
.lockmark.coral{color:var(--coral)}
.lockmark i{position:absolute;inset:-.4rem;border:1px solid currentColor;opacity:.4;animation:ring 2.6s ease-out infinite}
@keyframes ring{0%{transform:scale(.8);opacity:.6}100%{transform:scale(1.5);opacity:0}}
.bad{color:var(--coral);font-size:.76rem;letter-spacing:.16em;text-transform:uppercase;margin:0 0 .6rem}
.shake{animation:shk .45s cubic-bezier(.36,.07,.19,.97)}
@keyframes shk{10%,90%{transform:translateX(-2px)}20%,80%{transform:translateX(4px)}
  30%,50%,70%{transform:translateX(-7px)}40%,60%{transform:translateX(7px)}}

/* ---------- reveal ---------- */
.rv{opacity:0;transform:translateY(20px);transition:opacity .75s cubic-bezier(.2,.7,.2,1),transform .75s cubic-bezier(.2,.7,.2,1)}
.rv.in{opacity:1;transform:none}
::selection{background:var(--amber);color:#0b1a26}
CSS);

/* ════════════════════════════════ JS ══════════════════════════════════════ */

define('JS', <<<'JS'
(function () {
  var d = document, $ = function (s, r) { return (r || d).querySelector(s); },
      $$ = function (s, r) { return Array.prototype.slice.call((r || d).querySelectorAll(s)); };
  var ALPHA = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

  /* ---- clipboard ---- */
  function copy(text, btn) {
    var done = function () {
      var el = $('.bt', btn) || btn, prev = el.textContent;
      el.textContent = 'copied ✓'; btn.classList.add('done');
      setTimeout(function () { el.textContent = prev; btn.classList.remove('done'); }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, fallback);
    } else fallback();
    function fallback() {
      var t = d.createElement('textarea'); t.value = text;
      t.style.cssText = 'position:fixed;top:-1000px;opacity:0'; d.body.appendChild(t);
      t.select(); try { d.execCommand('copy'); } catch (e) {} t.remove(); done();
    }
  }
  $$('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function (e) { e.preventDefault(); copy(b.getAttribute('data-copy'), b); });
  });
  $$('[data-select]').forEach(function (el) {
    el.addEventListener('click', function () {
      var r = d.createRange(); r.selectNodeContents(el);
      var s = getSelection(); s.removeAllRanges(); s.addRange(r);
    });
  });

  /* ---- live preview ---- */
  var body = d.body, BASE = body.dataset.base || '/', HOST = body.dataset.host || 'localhost',
      PATH = body.dataset.path || '/',
      urlIn = $('#url-in'), slugIn = $('#slug-in'), ttlSel = $('#ttl-sel'), daysIn = $('#ttl-days'),
      neverChk = $('#never-chk'), guardChk = $('#guard-chk'), pwField = $('#pw-field'),
      daysField = $('#days-field'), pvSlug = $('#pv-slug'), pvExp = $('#pv-exp'),
      pvLife = $('#pv-life'), pvGuard = $('#pv-guard'), pvHost = $('#pv-host'), pvPath = $('#pv-path');

  if (pvHost) pvHost.textContent = HOST;
  if (pvPath) pvPath.textContent = HOST + PATH;

  function roll(len) {
    len = len || 5; var out = '', buf = new Uint32Array(len);
    (window.crypto || window.msCrypto).getRandomValues(buf);
    for (var i = 0; i < len; i++) out += ALPHA[buf[i] % ALPHA.length];
    return out;
  }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function iso(days) {
    var t = new Date(Date.now() + days * 864e5);
    return t.getFullYear() + '-' + pad(t.getMonth() + 1) + '-' + pad(t.getDate());
  }
  function label(days) {
    if (days === 1) return '1 day';
    if (days === 7) return '1 week';
    if (days === 30) return '1 month';
    if (days === 90) return '3 months';
    if (days === 365) return '1 year';
    return days + ' days';
  }
  function sync(flashSlug) {
    if (!pvSlug) return;
    var s = (slugIn.value || '').trim();
    if (pvSlug.textContent !== (s || '·····')) {
      pvSlug.textContent = s || '·····';
      if (flashSlug) { pvSlug.classList.remove('roll'); void pvSlug.offsetWidth; pvSlug.classList.add('roll'); }
    }
    var never = neverChk && neverChk.checked;
    var days = 365;
    if (!never && ttlSel) {
      days = ttlSel.value === '0' ? Math.max(1, Math.min(3650, parseInt(daysIn.value || '0', 10) || 365))
                                  : parseInt(ttlSel.value, 10);
    }
    pvLife.textContent = never ? 'forever' : label(days);
    pvExp.textContent  = never ? 'never' : iso(days);
    pvExp.style.color  = never ? 'var(--mint)' : '';
    pvGuard.textContent = (guardChk && guardChk.checked) ? 'password required' : 'none';
    pvGuard.style.color = (guardChk && guardChk.checked) ? 'var(--sky)' : '';
    if (ttlSel) ttlSel.disabled = !!never;
    var useCustom = !!ttlSel && ttlSel.value === '0' && !never;
    if (daysField) daysField.hidden = !useCustom;
    if (daysIn) daysIn.disabled = !useCustom;
	if (pwField) {
      var open = !!(guardChk && guardChk.checked);
      if (open && pwField.hidden) { pwField.hidden = false; $('#pw-in').focus(); }
      if (!open && !pwField.hidden) pwField.hidden = true;
    }
  }
  ['input', 'change'].forEach(function (ev) {
    d.addEventListener(ev, function (e) {
      if (e.target.matches('#url-in,#slug-in,#ttl-sel,#ttl-days,#never-chk,#guard-chk')) sync(e.target === slugIn);
    });
  });
  if (slugIn) slugIn.addEventListener('input', function () {
    slugIn.value = slugIn.value.replace(/[^A-Za-z0-9_\-]/g, '');
  });
  var dice = $('#dice');
  if (dice) dice.addEventListener('click', function () {
    slugIn.value = roll(); slugIn.focus(); sync(true);
  });
  if (urlIn) urlIn.addEventListener('input', function () {
    if (slugIn && !slugIn.value) {
      var tail = urlIn.value.replace(/^https?:\/\//i, '').split(/[\/?#]/).filter(Boolean).pop() || '';
      tail = tail.replace(/[^A-Za-z0-9_\-]/g, '').slice(0, 12);
      pvSlug.textContent = tail || '·····';
    }
  });
  sync(false);

  /* ---- keyboard ---- */
  d.addEventListener('keydown', function (e) {
    if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(d.activeElement.tagName)) {
      e.preventDefault(); if (urlIn) urlIn.focus();
    }
  });

  /* ---- reveal on scroll ---- */
  var io = ('IntersectionObserver' in window) && new IntersectionObserver(function (en) {
    en.forEach(function (x) { if (x.isIntersecting) { x.target.classList.add('in'); io.unobserve(x.target); } });
  }, { threshold: .12, rootMargin: '0px 0px -8%' });
  $$('.rv').forEach(function (el) { io ? io.observe(el) : el.classList.add('in'); });

  /* ---- count-up ---- */
  $$('.cu').forEach(function (el) {
    var to = parseInt(el.dataset.count || '0', 10) || 0, t0 = null, dur = 900;
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) { el.textContent = to; return; }
    function step(t) {
      if (!t0) t0 = t;
      var p = Math.min(1, (t - t0) / dur);
      el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });

  /* ---- clock ---- */
  var clock = $('#clock');
  if (clock) setInterval(function () {
    var n = new Date();
    clock.textContent = pad(n.getHours()) + ':' + pad(n.getMinutes()) + ':' + pad(n.getSeconds());
  }, 1000);

  /* ---- scroll to fresh result ---- */
  if ($('#result')) window.scrollTo({ top: 0, behavior: 'smooth' });
})();
JS);
/* ══════════════════════════════ boot ══════════════════════════════════════ */

session_set_cookie_params(['samesite' => 'Lax', 'httponly' => true]);
session_start();

try {
    $pdo = db();
} catch (Throwable $t) {
    http_response_code(500);
    exit('<h1 style="font:600 16px/1.5 monospace;padding:2rem">SQLite could not open '
        . htmlspecialchars(CFG['db']) . '<br>' . htmlspecialchars($t->getMessage()) . '</h1>');
}

/* ═══════════════════════════════ db ═══════════════════════════════════════ */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $fresh = !is_file(CFG['db']);
    $pdo = new PDO('sqlite:' . CFG['db'], null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA journal_mode  = WAL');
    $pdo->exec('PRAGMA synchronous   = NORMAL');

    $pdo->exec('CREATE TABLE IF NOT EXISTS links (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        slug         TEXT    NOT NULL UNIQUE,
        url          TEXT    NOT NULL,
        pass         TEXT    NULL,           -- bcrypt hash, NULL = public
        expires_at   INTEGER NULL,           -- unix ts, NULL = never
        created_at   INTEGER NOT NULL,
        clicks       INTEGER NOT NULL DEFAULT 0,
        ip           TEXT    NULL,
        remove_token TEXT    NOT NULL
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS hits (slug TEXT NOT NULL, ts INTEGER NOT NULL)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_hits_slug ON hits(slug, ts)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_links_exp  ON links(expires_at)');

    if ($fresh) @chmod(CFG['db'], 0640);
    return $pdo;
}

/* ═════════════════════════════ helpers ════════════════════════════════════ */

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
/* ---------- UTF-8 helpers, safe when mbstring is unavailable ---------- */

if (!function_exists('utf8_strlen')) {
    function utf8_strlen(string $s): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($s, 'UTF-8');
        }

        if (function_exists('iconv_strlen')) {
            $n = @iconv_strlen($s, 'UTF-8');
            if ($n !== false) {
                return (int) $n;
            }
        }

        // Fallback: count UTF-8 lead bytes.
        $len   = 0;
        $bytes = strlen($s);

        for ($i = 0; $i < $bytes; $i++) {
            if ((ord($s[$i]) & 0xC0) !== 0x80) {
                $len++;
            }
        }

        return $len;
    }
}

if (!function_exists('mb_strimwidth')) {
    function mb_strimwidth(
        string $str,
        int $start,
        int $width,
        string $trimmarker = '',
        ?string $encoding = null
    ): string {
        if ($width <= 0) {
            return '';
        }

        $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);

        if ($chars === false) {
            $chars = str_split($str);
        }

        if ($start < 0) {
            $start = max(0, count($chars) + $start);
        }

        if (count($chars) <= $start + $width) {
            return implode('', array_slice($chars, $start));
        }

        return implode('', array_slice($chars, $start, $width)) . $trimmarker;
    }
}

function this_script(): string
{
    return $_SERVER['SCRIPT_NAME'] ?? '/';
}

function base_url(): string
{
    if (trim(CFG['base_url']) !== '') return rtrim(CFG['base_url'], '/') . '/';
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
          || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
    $host  = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $dir   = rtrim(str_replace('\\', '/', dirname(this_script())), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir . '/';
}

function short_link(string $slug): string { return base_url() . '?slug=' . rawurlencode($slug); }
function kill_link(string $slug, string $tok): string
{
    return base_url() . '?action=remove&slug=' . rawurlencode($slug) . '&token=' . rawurlencode($tok);
}

function rand_slug(int $len): string
{
    $a = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 1 l i 0 o
    $s = '';
    for ($i = 0; $i < $len; $i++) $s .= $a[random_int(0, strlen($a) - 1)];
    return $s;
}

function token(): string { return bin2hex(random_bytes(16)); }

function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }

function ok_csrf($t): bool
{
    return is_string($t) && $t !== '' && hash_equals(csrf(), $t);
}

function flash(?array $set = null): ?array
{
    if ($set !== null) { $_SESSION['flash'] = $set; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function jump(string $qs = ''): never
{
    header('Location: ' . this_script() . ($qs !== '' ? '?' . $qs : ''), true, 302);
    exit;
}

function human_span(int $secs): string
{
    $secs = abs($secs);
    foreach ([['year', 31536000], ['month', 2592000], ['day', 86400], ['hour', 3600], ['minute', 60]] as [$n, $s]) {
        if ($secs >= $s) { $q = (int) floor($secs / $s); return $q . ' ' . $n . ($q > 1 ? 's' : ''); }
    }
    return $secs . 's';
}

function pretty_ts(?int $ts): string { return $ts ? date('j M Y', $ts) : '—'; }

function dead(array $r): bool { return $r['expires_at'] !== null && (int) $r['expires_at'] < time(); }

function url_shape(string $u): array
{
    $p = parse_url($u);

    if ($p === false) {
        return [$u, ''];
    }

    $host = $p['host'] ?? $u;

    if (isset($p['port'])) {
        $host .= ':' . $p['port'];
    }

    $path = $p['path'] ?? '';

    if (isset($p['query']) && $p['query'] !== '') {
        $path .= '?' . $p['query'];
    }

    return [$host, $path];
}

function no_cache(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Robots-Tag: noindex, nofollow');
}

function harden_url(string $u): bool
{
    if (!preg_match('#^https?://#i', $u)) return false;
    $p = parse_url($u);
    return !empty($p['host']) && strpos($p['host'], '.') !== false;
}

/* ══════════════════════════════ actions ═══════════════════════════════════ */

$op     = strtolower(trim((string) ($_POST['op'] ?? '')));
$action = strtolower(trim((string) ($_GET['action'] ?? '')));

/* --- JSON export ------------------------------------------------------- */
if (isset($_GET['export'])) {
    if (!admin_ok()) { http_response_code(403); exit('{"error":"admin authentication required"}'); }
    no_cache();
    header('Content-Type: application/json; charset=utf-8');
    $rows = $pdo->query('SELECT slug,url,pass IS NOT NULL AS guarded,expires_at,created_at,clicks
                         FROM links ORDER BY created_at DESC')->fetchAll();
    foreach ($rows as &$r) {
        $r['guarded']  = (bool) $r['guarded'];
        $r['short']    = short_link($r['slug']);
        $r['expired']  = $r['expires_at'] !== null && (int) $r['expires_at'] < time();
        unset($r['pass']);
    }
    exit(json_encode(['base' => base_url(), 'generated' => date(DATE_ATOM), 'links' => $rows],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/* --- POST -------------------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    if (!ok_csrf($_POST['csrf'] ?? null)) { flash(['error' => 'Stale form — the CSRF token did not match. Try again.']); jump(); }

    if ($op === 'create')      handle_create($pdo);
    if ($op === 'unlock')      handle_unlock($pdo);
    if ($op === 'remove')      handle_remove($pdo);
    if ($op === 'purge')       handle_purge($pdo);
    if ($op === 'admin')       handle_admin_login($pdo);
    if ($op === 'hash')        handle_hash_gen();
    flash(['error' => 'Unknown operation.']); jump();
}

/* --- GET: remove confirmation ----------------------------------------- */
if ($action === 'remove') {
    require_admin();
    $row = find_link($pdo, (string) ($_GET['slug'] ?? ''));
    $tok = (string) ($_GET['token'] ?? '');
    if (!$row || !$tok || !hash_equals((string) $row['remove_token'], $tok)) render_bad_request($row);
    render_confirm($row);
}

/* --- GET: follow a short link ----------------------------------------- */
if (isset($_GET['slug']) && trim((string) $_GET['slug']) !== '') {
    handle_visit($pdo, trim((string) $_GET['slug']));
}

/* --- GET: the console ------------------------------------------------- */
require_admin();
render_admin($pdo);

/* ---------------------------------------------------------------- create */
function handle_create(PDO $pdo): void
{
    $fail = static function (string $m) {
        $_SESSION['old'] = $_POST;
        flash(['error' => $m]);
        jump();
    };

    $url = trim((string) ($_POST['url'] ?? ''));
    $url = preg_replace('/\s+/u', '', $url);
    if ($url === '')                        $fail('Paste a destination URL first.');
    if (strlen($url) > 2048)                $fail('That URL is longer than 2048 characters — trim it.');
    if (!preg_match('#^[a-z]+://#i', $url)) $url = 'https://' . $url;   // bare domain → https
    if (!harden_url($url))                  $fail('That does not look like a reachable http(s) URL.');

    /* slug ------------------------------------------------------------- */
    $custom = trim((string) ($_POST['slug'] ?? ''));
    $slug   = '';
    if ($custom !== '') {
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_\-]{2,31}$/', $custom))
            $fail('Slugs are 3–32 characters: letters, digits, dash and underscore, starting with a letter or digit.');
        if (in_array(strtolower($custom), CFG['reserved'], true)) $fail("“{$custom}” is reserved. Pick another.");
        if (find_link($pdo, $custom))                             $fail("“{$custom}” is already taken.");
        $slug = $custom;
    } else {
        for ($i = 0; $i < 14; $i++) {
            $try  = rand_slug((int) CFG['slug_len']);
            if (!find_link($pdo, $try)) { $slug = $try; break; }
        }
        if ($slug === '') $fail('Could not roll a free slug — try a custom one.');
    }

    /* password guard ---------------------------------------------------- */
    $hash = null;
    if (($_POST['guard'] ?? '') === '1') {
        $pw = (string) ($_POST['password'] ?? '');
        if (utf8_strlen($pw) < 4)   $fail('The guard password needs at least 4 characters.');
        if (strlen($pw) > 200)      $fail('That password is absurdly long. 200 characters max.');
        $hash = password_hash($pw, PASSWORD_DEFAULT);
    }

    /* expiry ------------------------------------------------------------ */
    $expires = null;
    if (($_POST['never'] ?? '') !== '1') {
        $pick = (string) ($_POST['ttl'] ?? (string) CFG['default_ttl']);
        if ($pick === '0') {
            $days = (int) ($_POST['ttl_days'] ?? CFG['default_ttl']);
            $days = max(1, min(3650, $days ?: (int) CFG['default_ttl']));
        } elseif (isset(TTLS[$pick])) {
            $days = (int) $pick;
        } else {
            $days = (int) CFG['default_ttl'];
        }
        $expires = time() + $days * 86400;
    }

    $tok = token();
    try {
        $pdo->prepare('INSERT INTO links (slug,url,pass,expires_at,created_at,clicks,ip,remove_token)
                       VALUES (:s,:u,:p,:x,:c,0,:i,:t)')
            ->execute([
                ':s' => $slug, ':u' => $url, ':p' => $hash, ':x' => $expires,
                ':c' => time(), ':t' => $tok,
                ':i' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ]);
    } catch (PDOException $ex) {
        $fail('Insert failed: ' . $ex->getMessage());
    }

    $_SESSION['old'] = [];
    flash(['created' => ['slug' => $slug, 'token' => $tok]]);
    jump();
}

/* ----------------------------------------------------------------- visit */
function handle_visit(PDO $pdo, string $slug): void
{
    $row = find_link($pdo, $slug);

    if (!$row)                                  render_missing($slug, 'nosuch');
    if (dead($row))                             render_missing($slug, 'expired');
    if ($row['pass'] !== null && !unlocked($slug)) render_gate($row);

    $pdo->prepare('UPDATE links SET clicks = clicks + 1 WHERE id = ?')->execute([$row['id']]);
    if (CFG['log_clicks']) {
        $pdo->prepare('INSERT INTO hits (slug,ts) VALUES (?,?)')->execute([$row['slug'], time()]);
    }
    header('Cache-Control: no-store');
    header('Location: ' . $row['url'], true, 302);
    exit;
}

function unlocked(string $slug): bool
{
    $until = $_SESSION['unlocked'][$slug] ?? 0;
    if ($until > time()) return true;
    unset($_SESSION['unlocked'][$slug]);
    return false;
}

function handle_unlock(PDO $pdo): void
{
    $slug = (string) ($_POST['slug'] ?? '');
    $row  = find_link($pdo, $slug);
    if (!$row) render_missing($slug, 'nosuch');

    if (!password_verify((string) ($_POST['password'] ?? ''), (string) $row['pass'])) {
        $_SESSION['old'] = ['slug' => $slug];
        render_gate($row, true);
    }
    $_SESSION['unlocked'][$slug] = time() + 3600;   // one hour of free passage
    header('Location: ' . short_link($slug), true, 302);
    exit;
}

/* ---------------------------------------------------------------- remove */
function handle_remove(PDO $pdo): void
{
    $row = find_link($pdo, (string) ($_POST['slug'] ?? ''));
    if (!$row || !hash_equals((string) $row['remove_token'], (string) ($_POST['token'] ?? ''))) {
        render_bad_request($row);
    }
    $pdo->prepare('DELETE FROM links WHERE id = ?')->execute([$row['id']]);
    $pdo->prepare('DELETE FROM hits  WHERE slug = ?')->execute([$row['slug']]);
    unset($_SESSION['unlocked'][$row['slug']]);

    flash(['removed' => ['slug' => $row['slug'], 'url' => $row['url'], 'clicks' => (int) $row['clicks']]]);
    jump();
}

function handle_purge(PDO $pdo): void
{
    $n = $pdo->prepare('DELETE FROM links WHERE expires_at IS NOT NULL AND expires_at < ?');
    $n->execute([time()]);
    $gone = $n->rowCount();
    $pdo->prepare('DELETE FROM hits WHERE slug NOT IN (SELECT slug FROM links)')->execute();
    flash($gone ? ['note' => "{$gone} expired link" . ($gone === 1 ? '' : 's') . " swept out of the manifest."]
                : ['note' => 'Nothing expired to sweep. The manifest is clean.']);
    jump();
}

/* ----------------------------------------------------------------- admin */
function admin_required(): bool { return trim(CFG['admin_pass']) !== ''; }
function admin_ok(): bool       { return !admin_required() || !empty($_SESSION['admin_ok']); }

function require_admin(): void { if (!admin_ok()) render_admin_login(); }

function handle_admin_login(PDO $pdo): void
{
    $given = (string) ($_POST['password'] ?? '');
    $cfg   = trim(CFG['admin_pass']);
    $good  = preg_match('/^\$2[aby]\$|^\$argon2/', $cfg)
           ? password_verify($given, $cfg)
           : ($given !== '' && hash_equals($cfg, $given));
    if (!$good) render_admin_login(true);
    $_SESSION['admin_ok'] = true;
    jump();
}

function handle_hash_gen(): void
{
    no_cache();
    header('Content-Type: text/plain; charset=utf-8');
    $p = (string) ($_POST['password'] ?? '');
    echo $p === '' ? '(paste a password first)' : password_hash($p, PASSWORD_DEFAULT);
    exit;
}

/* ═══════════════════════════ data for views ═══════════════════════════════ */

function find_link(PDO $pdo, string $slug): ?array
{
    if ($slug === '' || strlen($slug) > 64) return null;
    $st = $pdo->prepare('SELECT * FROM links WHERE slug = ? LIMIT 1');
    $st->execute([$slug]);
    $r = $st->fetch();
    return $r ?: null;
}

function ledger(PDO $pdo): array
{
    $rows = $pdo->query('SELECT * FROM links ORDER BY created_at DESC, id DESC LIMIT 400')->fetchAll();

    $since = strtotime('-13 days', strtotime('today'));
    $spark = [];
    if (CFG['log_clicks'] && $rows) {
        $st = $pdo->prepare("SELECT slug, CAST((? - ts) / 86400 AS INTEGER) AS bucket, COUNT(*) AS c
                             FROM hits WHERE ts >= ? GROUP BY slug, bucket");
        $st->execute([$since, $since]);
        while ($r = $st->fetch()) {
            $b = 13 - max(0, min(13, (int) $r['bucket']));
            $spark[$r['slug']][$b] = (int) $r['c'];
        }
    }

    $s = $pdo->prepare('SELECT COUNT(*) total, COALESCE(SUM(clicks),0) clicks,
                               SUM(CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END) forever,
                               SUM(CASE WHEN expires_at IS NOT NULL AND expires_at >= ? THEN 1 ELSE 0 END) live,
                               SUM(CASE WHEN expires_at IS NOT NULL AND expires_at <  ? THEN 1 ELSE 0 END) dead,
                               SUM(CASE WHEN pass IS NOT NULL THEN 1 ELSE 0 END) guarded
                        FROM links');
    $s->execute([time(), time()]);
    $stats = $s->fetch();

    return ['rows' => $rows, 'spark' => $spark, 'stats' => $stats];
}

function spark_svg(array $cells): string
{
    $vals = [];
    for ($i = 0; $i < 14; $i++) $vals[] = (int) ($cells[$i] ?? 0);
    $max  = max(1, max($vals));
    $n    = count($vals);
    $w    = 88;
    $bw   = $w / $n - 1.2;
    $out  = '<svg class="spark" viewBox="0 0 ' . $w . ' 20" preserveAspectRatio="none" aria-hidden="true">';
    foreach ($vals as $i => $v) {
        $h = $v === 0 ? 1.4 : max(2, ($v / $max) * 18);
        $x = round($i * ($w / $n), 2);
        $y = round(19 - $h, 2);
        $cls = $v === 0 ? 'zero' : ($v === $max ? 'peak' : 'on');
        $out .= '<rect class="' . $cls . '" x="' . $x . '" y="' . $y . '" width="' . round($bw, 2)
              . '" height="' . $h . '"><title>' . $v . ' click' . ($v === 1 ? '' : 's') . '</title></rect>';
    }
    return $out . '</svg>';
}

function exp_cell(array $r): string
{
    if ($r['expires_at'] === null) return '<span class="pill mint">∞ never</span>';
    $left = (int) $r['expires_at'] - time();
    if ($left <= 0) return '<span class="pill coral">expired ' . human_span($left) . ' ago</span>';
    $cls = $left < 86400 * 30 ? 'amber' : 'fog';
    return '<span class="pill ' . $cls . '">' . pretty_ts((int) $r['expires_at']) . ' · ' . human_span($left) . '</span>';
}

/* ═════════════════════════════ chrome ═════════════════════════════════════ */

function head(string $title, string $crumbs = ''): void
{
    no_cache();
    $b = parse_url(base_url(), PHP_URL_HOST) ?: 'localhost';
    echo '<!doctype html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="dark">
<title>' . e($title) . '</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>' . CSS . '</style>
</head><body data-base="' . e(base_url()) . '" data-host="' . e($b) . '" data-path="' . e(parse_url(base_url(), PHP_URL_PATH) ?: '/') . '">
<div class="bg" aria-hidden="true">
  <div class="grid"></div><div class="wash"></div><div class="grain"></div>
  <svg class="wires" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice">
    <path class="w w1" d="M-40 700 C 240 700 260 380 520 380 S 900 620 1180 300 1500 220 1500 220"/>
    <path class="w w2" d="M-40 180 C 300 180 340 520 700 520 S 1080 200 1480 460"/>
    <path class="w w3" d="M-40 460 C 200 460 300 120 640 140 S 1100 520 1480 380"/>
    <circle class="pkt" r="3.5"><animateMotion dur="11s" repeatCount="indefinite" path="M-40 700 C 240 700 260 380 520 380 S 900 620 1180 300 1500 220 1500 220"/></circle>
    <circle class="pkt p2" r="3"><animateMotion dur="14s" begin="-4s" repeatCount="indefinite" path="M-40 180 C 300 180 340 520 700 520 S 1080 200 1480 460"/></circle>
    <circle class="pkt p3" r="2.5"><animateMotion dur="9s" begin="-2s" repeatCount="indefinite" path="M-40 460 C 200 460 300 120 640 140 S 1100 520 1480 380"/></circle>
  </svg>
</div>
<header class="topbar">
  <a class="brand" href="' . e(this_script()) . '">
    <span class="mark"><i></i><i></i><i></i></span>
    <span class="wm">RELAY</span>
    <span class="tag">php<span class="dotsep">·</span>sqlite<span class="dotsep">·</span>one file</span>
  </a>
  <nav class="crumbs mono">' . $crumbs . '</nav>
</header><main>';
}

function foot(): void
{
    echo '</main>
<footer class="sitefoot">
  <span class="mono">' . e(parse_url(base_url(), PHP_URL_HOST) ?: 'localhost') . e(parse_url(base_url(), PHP_URL_PATH) ?: '/') . '</span>
  <span>links live one year by default · forever if you ask · password guards are bcrypt</span>
  <span class="mono">db: ' . e(basename(CFG['db'])) . '</span>
</footer>
<script>' . JS . '</script>
</body></html>';
}

/* ══════════════════════════════ views ═════════════════════════════════════ */

function render_admin(PDO $pdo): void
{
    $f     = flash();
    $old   = $_SESSION['old'] ?? [];
    $_SESSION['old'] = [];
    $L     = ledger($pdo);
    $rows  = $L['rows'];
    $stats = $L['stats'];
    $made  = null;
    if (!empty($f['created'])) $made = find_link($pdo, $f['created']['slug']);
    $createdTok = $f['created']['token'] ?? '';

    head('RELAY — link forge', '<a href="' . e(this_script()) . '">console</a><span class="sep">/</span><span class="on">forge</span>');

    /* ── alert ─────────────────────────────────────────────────────── */
    if (!empty($f['error'])) {
        echo '<div class="band err rv in"><span class="band-k">blocked</span><p>' . e($f['error']) . '</p></div>';
    }
    if (!empty($f['note'])) {
        echo '<div class="band note rv in"><span class="band-k">ok</span><p>' . e($f['note']) . '</p></div>';
    }

    /* ── the freshly forged link ───────────────────────────────────── */
    if ($made) {
        $sl   = short_link($made['slug']);
        $kill = kill_link($made['slug'], $createdTok);
        [$host, $path] = url_shape($made['url']);
        ?><section class="band forged" id="result">
          <span class="stamp">forged</span>
          <p class="eyebrow">// your new short link</p>
          <div class="bigurl" data-select>
            <span class="sch"><?= e(parse_url($sl, PHP_URL_SCHEME)) ?>://</span><?= e(parse_url($sl, PHP_URL_HOST) . parse_url($sl, PHP_URL_PATH)) ?><span class="q">?slug=</span><b><?= e($made['slug']) ?></b>
          </div>
          <div class="rowbtns">
            <button class="btn primary" data-copy="<?= e($sl) ?>"><span class="bt">copy link</span></button>
            <a class="btn" href="<?= e($sl) ?>" target="_blank" rel="noopener">open it <span class="arrow">↗</span></a>
            <a class="btn ghost" href="#forge">forge another ↓</a>
          </div>

          <dl class="meta">
            <div><dt>destination</dt><dd><span class="mono"><b><?= e($host) ?></b><?= e($path) ?></span></dd></div>
            <div><dt>expires</dt><dd><?php
                if ($made['expires_at'] === null) echo '<span class="pill mint">∞ never</span>';
                else echo '<span class="pill amber">' . e(pretty_ts((int) $made['expires_at'])) . '</span> <span class="dim mono">in ' . e(human_span((int) $made['expires_at'] - time())) . '</span>';
            ?></dd></div>
            <div><dt>guard</dt><dd><?php echo $made['pass'] !== null
                ? '<span class="pill sky">password required</span>'
                : '<span class="pill fog">wide open</span>'; ?></dd></div>
            <div><dt>clicks</dt><dd class="mono">0</dd></div>
          </dl>

          <div class="killbox">
            <div class="kill-hd"><span class="key">⌧</span><b>remove link</b><em>keep this somewhere safe — anyone holding it can delete the link</em></div>
            <code class="killurl mono" data-select><?= e($kill) ?></code>
            <div class="rowbtns">
              <button class="btn tiny danger-out" data-copy="<?= e($kill) ?>"><span class="bt">copy remove link</span></button>
              <a class="btn tiny danger" href="<?= e($kill) ?>">remove it right now</a>
            </div>
          </div>
        </section><?php
    }

    /* ── tombstone ─────────────────────────────────────────────────── */
    if (!empty($f['removed'])) {
        $r = $f['removed'];
        ?><section class="band gone rv in">
          <span class="stamp grey">deleted</span>
          <p class="eyebrow">// route erased</p>
          <div class="bigurl struck"><span class="q">?slug=</span><b><?= e($r['slug']) ?></b></div>
          <p class="dim">It pointed at <span class="mono"><?= e($r['url']) ?></span> and collected
          <b><?= (int) $r['clicks'] ?></b> click<?= (int) $r['clicks'] === 1 ? '' : 's' ?>. Row and history purged.</p>
        </section><?php
    }

    /* ── the forge ─────────────────────────────────────────────────── */
    $val = static fn(string $k, string $d = '') => e((string) ($old[$k] ?? $d));
    $guardOn = ($_POST && ($old['guard'] ?? '') === '1') || (!$old && false);
    $neverOn = ($old['never'] ?? '') === '1';
    $ttlSel  = $old['ttl'] ?? (string) CFG['default_ttl'];
    ?><section class="forge" id="forge">
      <div class="forge-l">
        <p class="eyebrow">// route forge <span class="blink">_</span></p>
        <h1 class="display">PASTE SOMETHING<br><span class="stroke">ENORMOUS</span></h1>
        <p class="lede">One hop, one row in SQLite. Name the slug or let it roll, decide how long the
           route stays open, and optionally bolt a password on the door.</p>

        <form class="form" method="post" action="<?= e(this_script()) ?>" id="forge-form" autocomplete="off">
          <input type="hidden" name="op" value="create">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

          <label class="field span2">
            <span class="flabel">destination url <em>required</em></span>
            <input type="text" name="url" id="url-in" required placeholder="https://example.com/a/suspiciously/long/path?with=query&and=more"
                   value="<?= $val('url') ?>">
            <span class="fhint">bare domains get <code>https://</code> glued on automatically</span>
          </label>

          <div class="grid2 span2">
            <label class="field">
              <span class="flabel">custom slug <em>optional</em></span>
              <span class="with-btn">
                <input type="text" name="slug" id="slug-in" placeholder="leave blank = roll one" maxlength="32"
                       value="<?= $val('slug') ?>">
                <button type="button" class="dice" id="dice" title="roll a random slug" aria-label="roll a random slug">⚄</button>
              </span>
            </label>
            <label class="field">
              <span class="flabel">lives for</span>
              <select name="ttl" id="ttl-sel" <?= $neverOn ? 'disabled' : '' ?>>
                <?php foreach (TTLS as $k => $lab): ?>
                  <option value="<?= e((string) $k) ?>" <?= (string) $ttlSel === (string) $k ? 'selected' : '' ?>><?= e($lab) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>

          <label class="field span2" id="days-field" <?= ((string) $ttlSel === '0') ? '' : 'hidden' ?>>
            <span class="flabel">custom lifetime <em>only used when “custom…” is selected</em></span>
            <span class="with-btn narrow">
              <input type="number" name="ttl_days" id="ttl-days" min="1" max="3650"
                     value="<?= e((string) ($old['ttl_days'] ?? '45')) ?>"
                     <?= ((string) $ttlSel === '0') ? '' : 'disabled' ?>>
              <span class="unit">days</span>
            </span>
            <span class="fhint">If <b>Lives for</b> is set to <b>1 year</b>, this field is ignored.</span>
          </label>

          <div class="switches span2">
            <label class="sw">
              <input type="checkbox" name="guard" value="1" id="guard-chk" <?= ($old['guard'] ?? '') === '1' ? 'checked' : '' ?>>
              <span class="track"><span class="knob"></span></span>
              <span class="swtxt">password protect<small>visitors must know it</small></span>
            </label>
            <label class="sw">
              <input type="checkbox" name="never" value="1" id="never-chk" <?= $neverOn ? 'checked' : '' ?>>
              <span class="track"><span class="knob"></span></span>
              <span class="swtxt">never expire<small>ignores the lifetime above</small></span>
            </label>
          </div>

          <label class="field span2 pw" id="pw-field" <?= ($old['guard'] ?? '') === '1' ? 'data-open' : 'hidden' ?>>
            <span class="flabel">guard password <em>min 4 chars</em></span>
            <input type="password" name="password" id="pw-in" placeholder="••••••••">
            <span class="fhint">stored as a bcrypt hash — nobody, including this script, can read it back</span>
          </label>

          <div class="actions span2">
            <button class="btn primary lg" type="submit">forge short link <span class="arrow">→</span></button>
            <span class="hint mono">tip: press <kbd>/</kbd> anywhere to jump to the URL field</span>
          </div>
        </form>
      </div>

      <aside class="forge-r">
        <div class="panel preview">
          <div class="phd"><span>live output</span><span class="pulse"></span></div>
          <div class="pv-url" data-select>
            <span class="pv-path" id="pv-path">/shorturl/</span><span class="q">?slug=</span><b id="pv-slug">·····</b>
          </div>
          <ul class="pv-list">
            <li><span>host</span><b class="mono" id="pv-host">—</b></li>
            <li><span>lifetime</span><b class="mono" id="pv-life">1 year</b></li>
            <li><span>expires</span><b class="mono" id="pv-exp">—</b></li>
            <li><span>guard</span><b class="mono" id="pv-guard">none</b></li>
          </ul>
          <p class="pv-foot">The <b>remove link</b> is printed alongside every fresh short link —<br>store it, it is the only way back in.</p>
        </div>

        <div class="panel hop">
          <div class="phd"><span>one hop</span><span class="mono dim">302</span></div>
          <svg viewBox="0 0 300 96" class="hopsvg">
            <path id="hop" d="M26 66 C 90 66 100 24 160 24 S 240 62 276 30" fill="none"/>
            <use href="#hop" class="hopline"/>
            <g class="node"><rect x="18" y="58" width="16" height="16"/><span></span></g>
            <g class="node end"><rect x="268" y="22" width="16" height="16"/></g>
            <circle class="hop-pkt" r="4"><animateMotion dur="2.6s" repeatCount="indefinite"><mpath href="#hop"/></animateMotion></circle>
          </svg>
          <div class="hop-lbl"><span>visitor</span><span class="dim">relay row lookup</span><span>destination</span></div>
        </div>
      </aside>
    </section><?php

    /* ── the manifest ──────────────────────────────────────────────── */
    $deadCount = (int) ($stats['dead'] ?? 0);
    ?><section class="manifest rv">
      <div class="mf-hd">
        <h2>THE<br>MANIFEST</h2>
        <div class="mf-tools">
          <a class="btn tiny ghost-out" href="<?= e(this_script()) ?>?export=json" target="_blank">json</a>
          <?php if ($deadCount): ?>
          <form method="post" action="<?= e(this_script()) ?>" class="inline"
                onsubmit="return confirm('Sweep <?= $deadCount ?> expired link(s) out of the database?');">
            <input type="hidden" name="op" value="purge"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <button class="btn tiny ghost-out" type="submit">sweep <?= $deadCount ?> expired</button>
          </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="mf-body">
        <div class="tablewrap">
          <table class="ledger">
            <thead><tr>
              <th>slug</th><th>destination</th><th>clicks <span class="th-sub">14d</span></th>
              <th>born</th><th>expires</th><th>flags</th><th class="ta-r">actions</th>
            </tr></thead>
            <tbody>
            <?php if (!$rows): ?>
              <tr class="empty"><td colspan="7">
                <div class="emptyspace"><span>▚</span><p>No routes yet. Forge one above — the ledger fills itself in.</p></div>
              </td></tr>
            <?php else: foreach ($rows as $r):
                $sl   = short_link($r['slug']);
                $kill = kill_link($r['slug'], (string) $r['remove_token']);
                [$host, $path] = url_shape($r['url']);
                $isDead = dead($r);
            ?>
              <tr class="<?= $isDead ? 'is-dead' : '' ?>">
                <td data-l="slug">
                  <a class="slugline" href="<?= e($sl) ?>" target="_blank" rel="noopener" title="open short link">
                    <b><?= e($r['slug']) ?></b><span class="arrow">↗</span>
                  </a>
                  <button class="mini" data-copy="<?= e($sl) ?>" title="copy short link">copy</button>
                </td>
                <td data-l="destination">
                  <!--<a class="dest" href="<?= e($r['url']) ?>" target="_blank" rel="noopener" title="<?= e($r['url']) ?>">
                    <span class="h"><?= e($host) ?></span><span class="p"><?= e(mb_strimwidth($path, 0, 46, '…')) ?></span>
                  </a> -->
                </td>
                <td data-l="clicks"><span class="clicks mono"><?= (int) $r['clicks'] ?></span>
                  <?= CFG['log_clicks'] ? spark_svg($L['spark'][$r['slug']] ?? []) : '' ?></td>
                <td data-l="born" class="mono dim"><?= e(date('j M y', (int) $r['created_at'])) ?></td>
                <td data-l="expires"><?= exp_cell($r) ?></td>
                <td data-l="flags" class="flags">
                  <?php if ($r['pass'] !== null): ?><span class="pill sky sm">lock</span><?php endif; ?>
                  <?php if ($r['expires_at'] === null): ?><span class="pill mint sm">∞</span><?php endif; ?>
                  <?php if ($isDead): ?><span class="pill coral sm">dead</span><?php endif; ?>
                </td>
                <td data-l="actions" class="ta-r">
                 <!-- <a class="mini danger" href="<?= e($kill) ?>" title="remove link">remove</a> -->
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <aside class="mf-rail">
          <div class="panel stat">
            <div class="phd"><span>counters</span></div>
            <ul>
              <li><b class="mono cu" data-count="<?= (int) ($stats['total'] ?? 0) ?>">0</b><span>links forged</span></li>
              <li><b class="mono cu" data-count="<?= (int) ($stats['clicks'] ?? 0) ?>">0</b><span>total clicks</span></li>
              <li><b class="mono cu" data-count="<?= (int) ($stats['live'] ?? 0) ?>">0</b><span>currently live</span></li>
              <li><b class="mono cu" data-count="<?= (int) ($stats['guarded'] ?? 0) ?>">0</b><span>behind a password</span></li>
              <li><b class="mono cu" data-count="<?= (int) ($stats['forever'] ?? 0) ?>">0</b><span>set to never expire</span></li>
            </ul>
          </div>
          <?php
          $top = $rows; usort($top, fn($a, $b) => (int) $b['clicks'] <=> (int) $a['clicks']);
          $top = array_values(array_filter(array_slice($top, 0, 5), fn($r) => (int) $r['clicks'] > 0));
          if ($top): ?>
          <div class="panel rank">
            <div class="phd"><span>most travelled</span></div>
            <ol>
              <?php $mx = max(array_map(fn($r) => (int) $r['clicks'], $top));
              foreach ($top as $i => $r): $c = (int) $r['clicks']; ?>
                <li>
                  <span class="rk mono"><?= $i + 1 ?></span>
                  <a href="<?= e(short_link($r['slug'])) ?>" target="_blank" rel="noopener" class="mono"><?= e($r['slug']) ?></a>
                  <span class="bar"><i style="--w:<?= round($c / $mx * 100) ?>%"></i></span>
                  <b class="mono"><?= $c ?></b>
                </li>
              <?php endforeach; ?>
            </ol>
          </div>
          <?php endif; ?>
          <?php
          $soon = array_values(array_filter($rows,
              fn($r) => $r['expires_at'] !== null && (int) $r['expires_at'] > time()
                    && (int) $r['expires_at'] - time() < 86400 * 60));
          usort($soon, fn($a, $b) => (int) $a['expires_at'] <=> (int) $b['expires_at']);
          $soon = array_slice($soon, 0, 5);
          if ($soon): ?>
          <div class="panel soon">
            <div class="phd"><span>about to lapse</span><span class="pulse amber"></span></div>
            <ul>
              <?php foreach ($soon as $r): ?>
                <li><a class="mono" href="<?= e(short_link($r['slug'])) ?>" target="_blank" rel="noopener"><?= e($r['slug']) ?></a>
                    <span><?= human_span((int) $r['expires_at'] - time()) ?></span></li>
              <?php endforeach; ?>
            </ul>
            <p class="tiny dim">Extend by re-forging with a new lifetime — expired routes are not resurrected.</p>
          </div>
          <?php endif; ?>
        </aside>
      </div>
    </section><?php

    foot();
}

/* ------------------------------------------------------------- gate page */
function render_gate(array $row, bool $wrong = false): void
{
    head('Protected link — RELAY', '<span>gate</span>');
    [$host, $path] = url_shape($row['url']);
    ?><section class="solo rv in <?= $wrong ? 'shake' : '' ?>">
      <div class="panel solo-p">
        <div class="lockmark">▣<i></i></div>
        <p class="eyebrow">// guarded route</p>
        <h1 class="display sm">THIS DOOR<br><span class="stroke">HAS A KEY</span></h1>
        <p class="lede"><span class="mono amber-text"><?= e($row['slug']) ?></span> leads to
           <b><?= e($host) ?></b><?= e(mb_strimwidth($path, 0, 40, '…')) ?> — and the owner locked it.</p>
        <?php if ($wrong): ?><p class="bad mono">wrong key. try again.</p><?php endif; ?>
        <form method="post" action="<?= e(this_script()) ?>" class="form">
          <input type="hidden" name="op" value="unlock">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
          <input type="hidden" name="slug" value="<?= e($row['slug']) ?>">
          <label class="field"><span class="flabel">password</span>
            <input type="password" name="password" autofocus required placeholder="••••••••"></label>
          <button class="btn primary wide" type="submit">unlock <span class="arrow">→</span></button>
        </form>
        <p class="tiny dim">Unlocked for one hour, then it asks again.</p>
      </div>
    </section><?php
    foot();
}

/* -------------------------------------------------------- 404 / expired */
function render_missing(string $slug, string $why): void
{
    http_response_code($why === 'expired' ? 410 : 404);
    head(($why === 'expired' ? 'Link expired' : 'No such link') . ' — RELAY', '<span>dead end</span>');
    $t = $why === 'expired'
        ? ['THIS ROUTE', 'CLOSED', 'It served its purpose and then the clock ran out. Nothing to see behind the barrier.']
        : ['NO SUCH', 'SLUG', 'Nothing in the database answers to that name. It may never have existed.'];
    ?><section class="solo rv in">
      <div class="panel solo-p">
        <div class="lockmark <?= $why === 'expired' ? 'coral' : '' ?>">⌫<i></i></div>
        <p class="eyebrow">// <?= e($why === 'expired' ? '410 gone' : '404 not found') ?></p>
        <h1 class="display sm"><?= $t[0] ?><br><span class="stroke"><?= $t[1] ?></span></h1>
        <p class="lede"><?= $t[2] ?></p>
        <?php if ($slug !== ''): ?><p class="mono dim struck">?slug=<?= e($slug) ?></p><?php endif; ?>
        <a class="btn" href="<?= e(this_script()) ?>">back to the forge <span class="arrow">→</span></a>
      </div>
    </section><?php
    foot();
}

/* ----------------------------------------------------- remove confirm */
function render_confirm(array $row): void
{
    head('Confirm removal — RELAY', '<span>remove</span>');
    [$host, $path] = url_shape($row['url']);
    ?><section class="solo rv in">
      <div class="panel solo-p danger-p">
        <div class="lockmark coral">⌧<i></i></div>
        <p class="eyebrow">// destructive</p>
        <h1 class="display sm">PULL THE<br><span class="stroke">PLUG?</span></h1>
        <p class="lede"><span class="mono amber-text"><?= e($row['slug']) ?></span> → <b><?= e($host) ?></b><?= e(mb_strimwidth($path, 0, 44, '…')) ?></p>
        <dl class="meta tight">
          <div><dt>clicks</dt><dd class="mono"><?= (int) $row['clicks'] ?></dd></div>
          <div><dt>born</dt><dd class="mono"><?= e(date('j M Y H:i', (int) $row['created_at'])) ?></dd></div>
          <div><dt>expires</dt><dd><?= exp_cell($row) ?></dd></div>
        </dl>
        <form method="post" action="<?= e(this_script()) ?>" class="form row-gap">
          <input type="hidden" name="op" value="remove">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
          <input type="hidden" name="slug" value="<?= e($row['slug']) ?>">
          <input type="hidden" name="token" value="<?= e($row['remove_token']) ?>">
          <div class="rowbtns">
            <button class="btn danger" type="submit">yes, delete it</button>
            <a class="btn ghost" href="<?= e(this_script()) ?>">keep it alive</a>
          </div>
        </form>
        <p class="tiny dim">The row and its click history are erased. There is no undo.</p>
      </div>
    </section><?php
    foot();
}

function render_bad_request(?array $row): void
{
    http_response_code(400);
    head('Bad remove link — RELAY', '<span>400</span>');
    ?><section class="solo rv in"><div class="panel solo-p">
      <p class="eyebrow">// rejected</p>
      <h1 class="display sm">THAT REMOVE<br><span class="stroke">TOKEN IS VOID</span></h1>
      <p class="lede">Remove tokens travel with the link that spawned them. This one either never matched
        <?= $row ? 'the record for <span class="mono">' . e($row['slug']) . '</span>' : 'any record' ?> or the link is already gone.</p>
      <a class="btn" href="<?= e(this_script()) ?>">back to the forge</a>
    </div></section><?php
    foot();
}

function render_admin_login(bool $wrong = false): void
{
    head('Admin — RELAY', '<span>locked console</span>');
    ?><section class="solo rv in <?= $wrong ? 'shake' : '' ?>">
      <div class="panel solo-p">
        <div class="lockmark">▤<i></i></div>
        <p class="eyebrow">// console locked</p>
        <h1 class="display sm">OWNER<br><span class="stroke">ONLY</span></h1>
        <p class="lede">Short links still resolve normally for everyone else — this gate only guards the ledger.</p>
        <?php if ($wrong): ?><p class="bad mono">incorrect password</p><?php endif; ?>
        <form method="post" action="<?= e(this_script()) ?>" class="form">
          <input type="hidden" name="op" value="admin">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
          <label class="field"><span class="flabel">admin password</span>
            <input type="password" name="password" autofocus required placeholder="••••••••"></label>
          <button class="btn primary wide" type="submit">enter console <span class="arrow">→</span></button>
        </form>
      </div>
    </section><?php
    foot();
}

