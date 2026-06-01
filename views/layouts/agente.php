<?php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['rol_nombre'])) return;

$rol       = $_SESSION['rol_nombre'];
$email     = $_SESSION['email'] ?? '';
$nombre    = explode('@', $email)[0];
$apellidos = '';
$id_disp   = '';

try {
    $conn = Database::getInstance();
    if ($rol === 'Paciente' && isset($_SESSION['paciente_id'])) {
        $st = $conn->prepare("SELECT nombres, apellidos, ci FROM pacientes WHERE id = :id");
        $st->execute([':id' => $_SESSION['paciente_id']]);
        $p = $st->fetch(PDO::FETCH_ASSOC);
        if ($p) {
            $nombre    = htmlspecialchars($p['nombres'] ?? $nombre);
            $apellidos = htmlspecialchars($p['apellidos'] ?? '');
            $id_disp   = 'CI: ' . htmlspecialchars($p['ci'] ?? '');
        }
    } elseif ($rol === 'Médico') {
        $st = $conn->prepare("SELECT licencia_medica FROM medicos WHERE usuario_id = :uid");
        $st->execute([':uid' => $_SESSION['user_id']]);
        $m = $st->fetch(PDO::FETCH_ASSOC);
        if ($m) $id_disp = 'Lic: ' . htmlspecialchars($m['licencia_medica'] ?? '');
    } else {
        $id_disp = strtoupper(substr($rol,0,3)) . '-' . $_SESSION['user_id'];
    }
} catch (Exception $e) { $id_disp = ''; }

$cfg = [
    'Administrativo' => ['color'=>'#007A5E','light'=>'#e6f4f0','emoji'=>'⚙️'],
    'Médico'         => ['color'=>'#007A5E','light'=>'#e6f4f0','emoji'=>'🩺'],
    'Paciente'       => ['color'=>'#0ea5e9','light'=>'#e0f2fe','emoji'=>'👤'],
    'Farmacéutico'   => ['color'=>'#ca8a04','light'=>'#fefce8','emoji'=>'💊'],
    'Laboratorista'  => ['color'=>'#7c3aed','light'=>'#f5f3ff','emoji'=>'🔬'],
];
if (!isset($cfg[$rol])) return;
$c = $cfg[$rol];

// Módulos por rol con rutas reales
$modulos = [
    'Administrativo' => [
        ['icon'=>'👥','label'=>'Pacientes',        'url'=>'/pacientes',            'desc'=>'Gestión de expedientes de pacientes'],
        ['icon'=>'📅','label'=>'Citas',             'url'=>'/citas',                'desc'=>'Administrar citas médicas'],
        ['icon'=>'🕐','label'=>'Horarios Médicos',  'url'=>'/horarios',             'desc'=>'Configurar horarios del personal'],
        ['icon'=>'🏥','label'=>'Hospitalización',   'url'=>'/hospitalizacion',      'desc'=>'Gestión de camas y habitaciones'],
        ['icon'=>'🏢','label'=>'Sucursales',        'url'=>'/sucursal',             'desc'=>'Administrar sucursales'],
        ['icon'=>'🔬','label'=>'Laboratorio',       'url'=>'/laboratorio',          'desc'=>'Resultados de laboratorio clínico'],
        ['icon'=>'💊','label'=>'Farmacia',          'url'=>'/farmacia',             'desc'=>'Inventario de medicamentos'],
        ['icon'=>'📦','label'=>'Insumos',           'url'=>'/insumo',               'desc'=>'Inventario de insumos médicos'],
        ['icon'=>'🧾','label'=>'Facturación',       'url'=>'/facturacion',          'desc'=>'Gestión de facturas y cobros'],
        ['icon'=>'📄','label'=>'Reporte PDF',       'url'=>'/dashboard/export_pdf', 'desc'=>'Descargar informe gerencial en PDF'],
        ['icon'=>'📊','label'=>'Exportar CSV',      'url'=>'/dashboard/export_csv', 'desc'=>'Exportar datos de citas en CSV'],
    ],
    'Médico' => [
        ['icon'=>'📅','label'=>'Mi Agenda',         'url'=>'/citas',            'desc'=>'Ver y gestionar mis citas del día'],
        ['icon'=>'👥','label'=>'Mis Pacientes',     'url'=>'/pacientes',        'desc'=>'Expedientes de mis pacientes'],
        ['icon'=>'📋','label'=>'Historia Clínica',  'url'=>'/historia_clinica', 'desc'=>'Crear y editar historias clínicas'],
        ['icon'=>'🔬','label'=>'Laboratorio',       'url'=>'/laboratorio',      'desc'=>'Solicitar y revisar análisis clínicos'],
        ['icon'=>'🏥','label'=>'Hospitalización',   'url'=>'/hospitalizacion',  'desc'=>'Ver pacientes internados'],
    ],
    'Paciente' => [
        ['icon'=>'📅','label'=>'Mis Citas',          'url'=>'/citas', 'desc'=>'Ver y agendar mis citas médicas'],
        ['icon'=>'⭐','label'=>'Calificar Consulta', 'url'=>'/citas', 'desc'=>'Calificar mi última atención médica'],
    ],
    'Farmacéutico' => [
        ['icon'=>'📋','label'=>'Despachar Recetas',  'url'=>'/farmacia',    'desc'=>'Despachar recetas de pacientes'],
        ['icon'=>'📦','label'=>'Inventario',         'url'=>'/farmacia',    'desc'=>'Stock y gestión de medicamentos'],
        ['icon'=>'🧰','label'=>'Insumos',            'url'=>'/insumo',      'desc'=>'Inventario de insumos médicos'],
        ['icon'=>'🧾','label'=>'Facturación',        'url'=>'/facturacion', 'desc'=>'Emitir y gestionar facturas'],
    ],
    'Laboratorista' => [
        ['icon'=>'📋','label'=>'Análisis Pendientes','url'=>'/laboratorio',        'desc'=>'Ver análisis que esperan resultado'],
        ['icon'=>'✅','label'=>'Registrar Resultado','url'=>'/laboratorio',        'desc'=>'Ingresar resultados de análisis'],
        ['icon'=>'➕','label'=>'Nueva Orden',        'url'=>'/laboratorio/create', 'desc'=>'Crear nueva orden de laboratorio'],
    ],
];

$saludos = [
    'Administrativo' => "¡Hola, <strong>{nombre}</strong>! 👋<br>Selecciona un módulo para ir directo:",
    'Médico'         => "¡Buen día, <strong>Dr/a. {nombre}</strong>! 🩺<br>¿A dónde vamos hoy?",
    'Paciente'       => "¡Hola, <strong>{nombre}</strong>! 👋<br>¿En qué te puedo ayudar?",
    'Farmacéutico'   => "¡Hola, <strong>{nombre}</strong>! 💊<br>¿Qué módulo necesitas?",
    'Laboratorista'  => "¡Hola, <strong>{nombre}</strong>! 🔬<br>¿A dónde vamos hoy?",
];

$modulosRol  = $modulos[$rol]   ?? [];
$saludo      = str_replace('{nombre}', $nombre, $saludos[$rol] ?? "¡Hola, <strong>{$nombre}</strong>!");
$modulosJson = json_encode($modulosRol,  JSON_UNESCAPED_UNICODE);
$saludoJson  = json_encode($saludo,      JSON_UNESCAPED_UNICODE);
$baseJson    = json_encode(BASE_URL,     JSON_UNESCAPED_UNICODE);

// Respuestas por palabras clave
$respuestas = [];
foreach ($modulosRol as $mod) {
    $respuestas[] = $mod;
}
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
#crd-toggle{position:fixed;bottom:24px;right:24px;width:56px;height:56px;border-radius:50%;
  background:linear-gradient(145deg,<?=$c['color']?>cc,<?=$c['color']?>);border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 4px 18px <?=$c['color']?>55;transition:transform .25s,box-shadow .25s;z-index:99998;}
#crd-toggle:hover{transform:scale(1.08);}
#crd-toggle svg{position:absolute;transition:opacity .2s,transform .2s;}
.crd-ico-c{}
.crd-ico-x{opacity:0;transform:rotate(-90deg);}
#crd-toggle.open .crd-ico-c{opacity:0;transform:rotate(90deg);}
#crd-toggle.open .crd-ico-x{opacity:1;transform:rotate(0);}
#crd-toggle::before{content:'';position:absolute;width:100%;height:100%;border-radius:50%;
  background:<?=$c['color']?>;opacity:.2;animation:crd-pulse 2.5s ease-out infinite;}
@keyframes crd-pulse{0%{transform:scale(1);opacity:.2}70%{transform:scale(1.75);opacity:0}100%{transform:scale(1.75);opacity:0}}

#crd-win{position:fixed;bottom:94px;right:24px;width:350px;max-height:600px;
  background:#fff;border-radius:18px;box-shadow:0 20px 50px rgba(0,0,0,.15);
  display:flex;flex-direction:column;overflow:hidden;z-index:99999;
  transform:scale(.85) translateY(22px);opacity:0;pointer-events:none;
  transition:transform .3s cubic-bezier(.34,1.56,.64,1),opacity .25s;}
#crd-win.open{transform:scale(1) translateY(0);opacity:1;pointer-events:all;}

.crd-header{background:linear-gradient(135deg,<?=$c['color']?>dd,<?=$c['color']?>);
  padding:.85rem 1.1rem;display:flex;align-items:center;gap:.65rem;flex-shrink:0;}
.crd-hav{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);
  border:2px solid rgba(255,255,255,.35);display:flex;align-items:center;justify-content:center;
  font-size:1.15rem;flex-shrink:0;}
.crd-htxt h3{font-family:'Outfit',sans-serif;font-weight:700;font-size:.9rem;color:#fff;margin:0;}
.crd-htxt .sub{font-size:.67rem;color:rgba(255,255,255,.72);display:flex;align-items:center;gap:.28rem;}
.crd-dot{width:6px;height:6px;background:#4ade80;border-radius:50%;animation:crd-blink 1.8s ease-in-out infinite;}
@keyframes crd-blink{0%,100%{opacity:1}50%{opacity:.3}}

.crd-ucard{margin:.65rem .9rem .15rem;padding:.58rem .8rem;border-radius:12px;
  background:<?=$c['light']?>;border:1px solid <?=$c['color']?>33;
  display:flex;align-items:center;gap:.55rem;flex-shrink:0;}
.crd-uav{width:32px;height:32px;border-radius:50%;background:<?=$c['color']?>;color:#fff;
  font-weight:700;font-size:.78rem;font-family:'Outfit',sans-serif;
  display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.crd-uname{font-family:'Outfit',sans-serif;font-weight:600;font-size:.8rem;color:#1e293b;}
.crd-umeta{font-family:'Outfit',sans-serif;font-size:.67rem;color:#64748b;}
.crd-ubadge{margin-left:auto;font-size:.62rem;font-weight:700;font-family:'Outfit',sans-serif;
  padding:.18rem .55rem;border-radius:999px;background:<?=$c['color']?>;color:#fff;white-space:nowrap;}

.crd-msgs{flex:1;overflow-y:auto;padding:.8rem;display:flex;flex-direction:column;
  gap:.6rem;scroll-behavior:smooth;}
.crd-msgs::-webkit-scrollbar{width:3px;}
.crd-msgs::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:3px;}

.crd-msg{display:flex;gap:.4rem;animation:crd-up .25s ease forwards;opacity:0;}
.crd-msg.user{flex-direction:row-reverse;}
@keyframes crd-up{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

.crd-mav{width:26px;height:26px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:700;font-family:'Outfit',sans-serif;}
.crd-msg.bot .crd-mav{background:<?=$c['light']?>;color:<?=$c['color']?>;border:1px solid <?=$c['color']?>;}
.crd-msg.user .crd-mav{background:<?=$c['color']?>;color:#fff;}

.crd-bub{max-width:85%;padding:.5rem .8rem;border-radius:13px;
  font-size:.8rem;line-height:1.6;font-family:'Outfit',sans-serif;}
.crd-msg.bot .crd-bub{background:#f1f5f9;color:#1e293b;border-bottom-left-radius:3px;}
.crd-msg.user .crd-bub{background:<?=$c['color']?>;color:#fff;border-bottom-right-radius:3px;}

/* Grid de módulos */
.crd-modgrid{display:flex;flex-direction:column;gap:.28rem;margin-top:.4rem;}
.crd-modlink{display:flex;align-items:center;gap:.55rem;
  background:#fff;border:1.5px solid <?=$c['color']?>22;border-radius:10px;
  padding:.42rem .7rem;text-decoration:none;transition:all .18s;cursor:pointer;}
.crd-modlink:hover{background:<?=$c['color']?>;border-color:<?=$c['color']?>;}
.crd-modlink:hover .crd-modlabel{color:#fff;}
.crd-modlink:hover .crd-moddesc{color:rgba(255,255,255,.75);}
.crd-modlink:hover .crd-modarr{color:#fff;}
.crd-modicon{font-size:1rem;flex-shrink:0;width:22px;text-align:center;}
.crd-modtext{flex:1;min-width:0;}
.crd-modlabel{font-family:'Outfit',sans-serif;font-weight:600;font-size:.79rem;color:<?=$c['color']?>;line-height:1.2;}
.crd-moddesc{font-family:'Outfit',sans-serif;font-size:.67rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.crd-modarr{color:<?=$c['color']?>;font-size:.75rem;opacity:.5;flex-shrink:0;}

/* Chips búsqueda */
.crd-searchbar{padding:.5rem .85rem .35rem;display:flex;gap:.3rem;flex-wrap:wrap;
  background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0;}
.crd-schip{background:#fff;border:1.5px solid <?=$c['color']?>44;color:<?=$c['color']?>;
  font-size:.67rem;font-weight:600;padding:.2rem .6rem;border-radius:999px;
  cursor:pointer;transition:all .15s;white-space:nowrap;font-family:'Outfit',sans-serif;}
.crd-schip:hover{background:<?=$c['color']?>;color:#fff;border-color:<?=$c['color']?>;}

.crd-inputbar{padding:.6rem .85rem;display:flex;gap:.4rem;
  border-top:1px solid #e2e8f0;background:#fff;flex-shrink:0;}
#crd-inp{flex:1;border:1.5px solid #e2e8f0;border-radius:999px;
  padding:.46rem .9rem;font-family:'Outfit',sans-serif;font-size:.81rem;
  color:#1e293b;outline:none;transition:border-color .2s;}
#crd-inp:focus{border-color:<?=$c['color']?>;}
#crd-sbtn{width:35px;height:35px;border-radius:50%;background:<?=$c['color']?>;
  border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:transform .2s;}
#crd-sbtn:hover{transform:scale(1.1);}
.crd-foot{text-align:center;font-size:.6rem;color:#94a3b8;padding:.28rem;background:#fff;font-family:'Outfit',sans-serif;}
@media(max-width:420px){#crd-win{width:calc(100vw - 18px);right:9px;bottom:84px;}}
</style>

<button id="crd-toggle" aria-label="Asistente CORDES">
  <svg class="crd-ico-c" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
  </svg>
  <svg class="crd-ico-x" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round">
    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
  </svg>
</button>

<div id="crd-win">
  <div class="crd-header">
    <div class="crd-hav"><?=$c['emoji']?></div>
    <div class="crd-htxt">
      <h3>Asistente CORDES</h3>
      <span class="sub"><span class="crd-dot"></span><?=htmlspecialchars($rol)?> · Caja de Salud</span>
    </div>
  </div>
  <div class="crd-ucard">
    <div class="crd-uav"><?=strtoupper(substr($nombre,0,1).substr($apellidos,0,1))?></div>
    <div style="flex:1;min-width:0">
      <div class="crd-uname"><?=htmlspecialchars($nombre)?><?=$apellidos?' '.htmlspecialchars($apellidos):''?></div>
      <div class="crd-umeta"><?=htmlspecialchars($id_disp)?> · Activo</div>
    </div>
    <span class="crd-ubadge"><?=htmlspecialchars($rol)?></span>
  </div>
  <div class="crd-searchbar" id="crd-chips"></div>
  <div class="crd-msgs" id="crd-msgs"></div>
  <div class="crd-inputbar">
    <input type="text" id="crd-inp" placeholder="¿A dónde quieres ir?" autocomplete="off" maxlength="200"/>
    <button id="crd-sbtn" aria-label="Enviar">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
      </svg>
    </button>
  </div>
  <div class="crd-foot">Sistema CORDES · Caja de Salud</div>
</div>

<script>
(function(){
  const BASE    = <?=$baseJson?>;
  const MODULOS = <?=$modulosJson?>;
  const SALUDO  = <?=$saludoJson?>;
  const EMOJI   = <?=json_encode($c['emoji'])?>;
  const NOMBRE  = <?=json_encode($nombre)?>;
  const APELL   = <?=json_encode($apellidos)?>;

  function initials(n,a){ return ((n||'?')[0]+((a||'')[0]||'')).toUpperCase(); }
  function norm(t){ return t.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,''); }

  // Construir lista de módulos como botones
  function modsHTML(lista){
    return '<div class="crd-modgrid">'+lista.map(m=>
      `<a href="${BASE}${m.url}" class="crd-modlink">
        <span class="crd-modicon">${m.icon}</span>
        <span class="crd-modtext">
          <div class="crd-modlabel">${m.label}</div>
          <div class="crd-moddesc">${m.desc}</div>
        </span>
        <span class="crd-modarr">→</span>
      </a>`
    ).join('')+'</div>';
  }

  // Buscar módulos por texto
  function buscarMods(text){
    const t = norm(text);
    return MODULOS.filter(m =>
      norm(m.label).includes(t) ||
      norm(m.desc).includes(t)  ||
      norm(m.url).includes(t)
    );
  }

  function getResp(text){
    const t = norm(text);
    // Saludos
    if(['hola','buenas','buenos','hey','buen dia','buen día','saludos'].some(k=>t.includes(k))){
      return SALUDO + modsHTML(MODULOS);
    }
    // "todo" o "módulos" → mostrar todos
    if(['todo','todos','modulo','módulo','menu','menú','opciones','inicio'].some(k=>t.includes(k))){
      return '¡Aquí tienes todos los módulos disponibles:' + modsHTML(MODULOS);
    }
    // Buscar coincidencia
    const hits = buscarMods(text);
    if(hits.length > 0){
      return hits.length === 1
        ? `Aquí puedes ir a <strong>${hits[0].label}</strong>:` + modsHTML(hits)
        : `Encontré ${hits.length} módulos relacionados:` + modsHTML(hits);
    }
    // Sin coincidencia → mostrar todos
    return `No encontré un módulo exacto para eso. Aquí están todas las opciones disponibles:` + modsHTML(MODULOS);
  }

  // DOM
  const toggle = document.getElementById('crd-toggle');
  const win    = document.getElementById('crd-win');
  const msgs   = document.getElementById('crd-msgs');
  const inp    = document.getElementById('crd-inp');
  const chips  = document.getElementById('crd-chips');
  let isOpen   = false;

  // Chips rápidos — primeros 4 módulos
  MODULOS.slice(0,4).forEach(m=>{
    const b=document.createElement('button');
    b.className='crd-schip';
    b.textContent=m.icon+' '+m.label;
    b.addEventListener('click',()=>{ window.location.href=BASE+m.url; });
    chips.appendChild(b);
  }); 

  function addMsg(html,role){
    const wrap=document.createElement('div'); wrap.className=`crd-msg ${role}`;
    const av=document.createElement('div'); av.className='crd-mav';
    av.textContent=role==='bot'?EMOJI:initials(NOMBRE,APELL);
    const bub=document.createElement('div'); bub.className='crd-bub'; bub.innerHTML=html;
    wrap.appendChild(av); wrap.appendChild(bub);
    msgs.appendChild(wrap); msgs.scrollTop=msgs.scrollHeight;
  }

  function send(text){
    text=(text||inp.value).trim(); if(!text) return;
    addMsg(text,'user'); inp.value='';
    setTimeout(()=>{ addMsg(getResp(text),'bot'); },300);
  }

  toggle.addEventListener('click',()=>{
    isOpen=!isOpen;
    win.classList.toggle('open',isOpen);
    toggle.classList.toggle('open',isOpen);
    if(isOpen&&msgs.children.length===0){
      setTimeout(()=>addMsg(SALUDO+modsHTML(MODULOS),'bot'),300);
    }
    if(isOpen) setTimeout(()=>inp.focus(),360);
  });

  document.getElementById('crd-sbtn').addEventListener('click',()=>send());
  inp.addEventListener('keydown',e=>{ if(e.key==='Enter') send(); });
})();
</script>