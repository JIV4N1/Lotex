<x-filament-panels::page>
<div class="lt-editor" x-data="{
 guideName:'', proposal:[], cropX:0, cropY:0, cropWidth:0, cropHeight:0, group:'', lots:[], shapes:[], draft:[], selected:'', image:null, width:1200, height:800, zoom:1, preview:false, message:'', busy:false,
 fit(){this.$nextTick(()=>{this.zoom=Math.min(2,Math.max(.05,(this.$refs.viewport.clientWidth-40)/this.width));});},
 async load(){ this.proposal=[]; this.message=''; try { const data=await $wire.loadMap(Number(this.group)); this.lots=data.lots; this.shapes=data.map?.lots||[]; this.width=data.map?.width||1200; this.height=data.map?.height||800; this.draft=[]; this.selected=''; this.image=null;this.guideName='';this.fit(); }catch(e){this.message='No se pudo cargar la lotificación.';} },
 upload(event){const file=event.target.files[0]; if(!file)return; if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>20000000){this.message='Selecciona JPG, PNG o WebP de hasta 20 MB.';return;} const url=URL.createObjectURL(file);const img=new Image();img.onload=()=>{if(img.width>10000||img.height>10000){URL.revokeObjectURL(url);this.message='La imagen excede 10000 píxeles.';return;} if(this.shapes.length&amp;&amp;(this.width!==img.width||this.height!==img.height)){URL.revokeObjectURL(url);this.message='La guía debe tener las mismas dimensiones que el trazado guardado.';return;}if(this.image)URL.revokeObjectURL(this.image);this.image=url;this.width=img.width;this.height=img.height;this.cropX=0;this.cropY=0;this.cropWidth=img.width;this.cropHeight=img.height;this.proposal=[];this.guideName=file.name;this.fit();};img.src=url;},
 add(event){if(this.busy||this.preview||!this.selected||!this.image)return;const p=new DOMPoint(event.clientX,event.clientY).matrixTransform(this.$refs.canvas.getScreenCTM().inverse());this.draft.push([Math.round(p.x),Math.round(p.y)]);},
 finish(){if(this.draft.length&lt;3||!this.selected)return;this.shapes=this.shapes.filter(s=>s.id!==Number(this.selected));this.shapes.push({id:Number(this.selected),points:this.draft});this.draft=[];this.message='Lote trazado. Revisa los vértices antes de publicar.';},
 name(id){const lot=this.lots.find(l=>l.id===id);return lot?'M'+lot.block+' · L'+lot.lot_number+' · '+lot.area_m2+' m²':'';},
 points(shape){return shape.points.map(p=>p.join(',')).join(' ');},
 center(shape,axis){return shape.points.reduce((sum,p)=>sum+p[axis],0)/shape.points.length;},
 async detect(){
 if(!this.image||this.busy)return;
 const x=Number(this.cropX),y=Number(this.cropY),w=Number(this.cropWidth),h=Number(this.cropHeight),group=this.group;
 if(x&lt;0||y&lt;0||w&lt;1||h&lt;1||x+w>this.width||y+h>this.height){this.message='Revisa el recorte: debe quedar dentro de la imagen.';return;}
 this.busy=true;this.message='Analizando con Gemini…';this.proposal=[];
 try{const source=new Image();source.src=this.image;await source.decode();const canvas=document.createElement('canvas');const scale=Math.min(1,2400/Math.max(w,h));canvas.width=Math.round(w*scale);canvas.height=Math.round(h*scale);canvas.getContext('2d').drawImage(source,x,y,w,h,0,0,canvas.width,canvas.height);
 const data=await $wire.detectLots(Number(group),canvas.toDataURL('image/jpeg',.9).split(',')[1]);
 if(group!==this.group)return;if(data.error){this.message=data.error;return;}
 this.proposal=data.lots.map(l=>({id:l.id,points:l.points.map(p=>[Math.round(x+p[0]*w/1000),Math.round(y+p[1]*h/1000)])}));
 this.message=this.proposal.length+' propuestas. '+data.missing+' lotes sin detectar; '+data.rejected+' resultados descartados. Revisa la superposición naranja antes de incorporar.';
 }catch(e){this.message='No se pudo analizar el plano. El trazado actual se conserva.';}finally{this.busy=false;}
 },
 acceptProposal(){const existing=new Set(this.shapes.map(s=>s.id));this.shapes.push(...this.proposal.filter(s=>!existing.has(s.id)));this.proposal=[];this.message='Propuesta incorporada sin reemplazar los lotes ya trazados. Revisa cada contorno antes de publicar.';},
 async save(){this.busy=true;this.message='';try{await $wire.saveMap(Number(this.group),{width:this.width,height:this.height,lots:this.shapes});this.message='Mapa publicado. La imagen guía no forma parte del mapa público.';}catch(e){this.message='No se pudo guardar. Revisa la lotificación y los polígonos.';}finally{this.busy=false;} }
}">
<header class="lt-intro">
<div><span class="lt-kicker">ESTUDIO DE MAPAS</span><h2>Del plano al mapa de tus lotes</h2><p>Carga una guía, revisa los contornos y publica una distribución clara.</p></div>
<div class="lt-counter"><strong x-text="shapes.length"></strong><span x-text="'de '+lots.length+' lotes trazados'"></span></div>
</header>
<div class="lt-workspace">
<aside class="lt-sidebar" aria-label="Configuración del trazado">
<section class="lt-section">
<h3><span class="lt-step">1</span> Preparar el plano</h3>
<label class="lt-field">Lotificación<select x-model="group" @change="load()" :disabled="busy"><option value="">Selecciona una lotificación</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->title }}</option>@endforeach</select></label>
<label class="lt-upload"><span class="lt-upload-icon" aria-hidden="true">↥</span><strong x-text="guideName||'Cargar imagen guía'"></strong><span>JPG, PNG o WebP · Hasta 20 MB</span><input type="file" accept="image/png,image/jpeg,image/webp" @change="upload($event)" :disabled="!group||busy"></label>
</section>
<section class="lt-section">
<h3><span class="lt-step">2</span> Trazar los lotes</h3>
<label class="lt-field">Lote en edición<select x-model="selected" @change="draft=[]" :disabled="!group||busy"><option value="">Elige un lote</option><template x-for="lot in lots" :key="lot.id"><option :value="lot.id" x-text="name(lot.id)"></option></template></select></label>
<template x-if="lots.find(l=>l.id===Number(selected))"><div class="lt-measures"><span>Medidas registradas</span><strong x-text="'Frente: '+(lots.find(l=>l.id===Number(selected)).frontage_m||'—')+' m · Fondo: '+(lots.find(l=>l.id===Number(selected)).depth_m||'—')+' m'"></strong></div></template>
<p class="lt-hint">Marca cada esquina sobre la imagen. Cierra el lote al terminar.</p>
<div class="lt-actions"><x-filament::button color="gray" type="button" @click="draft.pop()" x-bind:disabled="busy||!draft.length">Deshacer punto</x-filament::button><x-filament::button type="button" @click="finish()" x-bind:disabled="busy||draft.length&lt;3">Cerrar lote</x-filament::button></div>
<button class="lt-delete" type="button" @click="shapes=shapes.filter(s=>s.id!==Number(selected));draft=[]" :disabled="busy||!shapes.some(s=>s.id===Number(selected))">Quitar trazo del lote seleccionado</button>
</section>
<details class="lt-section lt-ai">
<summary><span class="lt-step">✦</span> Asistencia con Gemini</summary>
<p class="lt-hint">Genera contornos sugeridos para revisar sobre la guía.</p>
<details class="lt-crop"><summary>Ajustar zona de análisis</summary><p class="lt-hint">El recuadro azul indica el área que se enviará. Coordenadas en píxeles.</p><div class="lt-grid">
<label class="lt-field">Desde X<input type="number" min="0" x-model="cropX" :disabled="busy"></label><label class="lt-field">Desde Y<input type="number" min="0" x-model="cropY" :disabled="busy"></label>
<label class="lt-field">Ancho<input type="number" min="1" x-model="cropWidth" :disabled="busy"></label><label class="lt-field">Alto<input type="number" min="1" x-model="cropHeight" :disabled="busy"></label></div></details>
<p class="lt-privacy">Al analizar, se envía a Google el recorte seleccionado. La guía no se publica.</p>
<x-filament::button type="button" @click="detect()" x-bind:disabled="busy||!image||!group">Detectar lotes con Gemini</x-filament::button>
<div class="lt-proposals" x-show="proposal.length" x-cloak><strong x-text="proposal.length+' contornos por revisar'"></strong><p class="lt-hint">Se muestran en naranja. Incorporarlos conserva los trazos existentes.</p><div class="lt-actions"><x-filament::button type="button" @click="acceptProposal()" x-bind:disabled="busy">Incorporar</x-filament::button><x-filament::button color="gray" type="button" @click="proposal=[]" x-bind:disabled="busy">Descartar</x-filament::button></div></div>
</details>
</aside>
<main class="lt-stage">
<div class="lt-toolbar"><div><strong>Área de trabajo</strong><span x-text="preview?'Vista limpia':'Guía y contornos'"></span></div><div class="lt-view-controls">
<label class="lt-toggle"><input type="checkbox" x-model="preview"> Vista limpia</label>
<button type="button" @click="zoom=Math.max(.05,Number(zoom)-.1)" aria-label="Alejar">−</button><output x-text="Math.round(zoom*100)+'%'" aria-label="Nivel de zoom"></output><button type="button" @click="zoom=Math.min(3,Number(zoom)+.1)" aria-label="Acercar">+</button><button type="button" @click="fit()">Ajustar</button>
</div></div>
<div class="lt-notice" role="status" x-show="message" x-cloak><span x-text="message"></span></div>
<div class="lt-viewport" x-ref="viewport" wire:ignore :aria-busy="busy">
<div class="lt-empty" x-show="!image&amp;&amp;!shapes.length"><span aria-hidden="true">▱</span><h3>Tu plano comienza aquí</h3><p>Selecciona una lotificación y carga su imagen guía para empezar.</p><small>El mapa público mostrará únicamente los contornos aprobados.</small></div>
<svg x-show="image||shapes.length" x-ref="canvas" :viewBox="'0 0 '+width+' '+height" :style="'width:'+width*zoom+'px;max-width:none;display:block'" @click="add($event)" aria-label="Área de trazado: pulsa en cada esquina del lote">
<image :href="image||''" :width="width" :height="height" x-show="image&amp;&amp;!preview" opacity="0.65" />
<template x-for="shape in shapes" :key="shape.id"><g><polygon :points="points(shape)" fill="#245440" :fill-opacity="preview?0.15:0.12" stroke="#245440" stroke-width="2"/><text :x="center(shape,0)" :y="center(shape,1)" font-size="14" text-anchor="middle" fill="#102e24" x-text="name(shape.id)"></text></g></template>
<template x-for="shape in proposal" :key="shape.id"><g><polygon :points="points(shape)" fill="#f59e0b" fill-opacity="0.25" stroke="#b45309" stroke-width="3"/><text :x="center(shape,0)" :y="center(shape,1)" font-size="14" text-anchor="middle" x-text="name(shape.id)"></text></g></template>
<rect :x="cropX" :y="cropY" :width="cropWidth" :height="cropHeight" fill="none" stroke="#2563eb" stroke-width="2" stroke-dasharray="10 6" x-show="!preview" pointer-events="none"/>
<polyline :points="draft.map(p=>p.join(',')).join(' ')" fill="none" stroke="#dc2626" stroke-width="3"/>
<template x-for="(point,index) in draft" :key="index"><circle :cx="point[0]" :cy="point[1]" r="4" fill="#dc2626"/></template>
</svg>
</div>
<div class="lt-stage-footer"><span><i class="lt-dot lt-green"></i> Trazado <i class="lt-dot lt-orange"></i> Propuesta <i class="lt-dot lt-red"></i> En edición</span><span x-text="draft.length?draft.length+' puntos · Cierra el lote para conservarlos':'Desplázate por la guía y ajusta el zoom para trabajar con precisión.'"></span></div>
</main>
</div>
<footer class="lt-publish"><div><h3><span class="lt-step">3</span> Revisar y publicar</h3><p>Comprueba la vista limpia y la correspondencia de cada lote con el original.</p></div><x-filament::button type="button" @click="save()" x-bind:disabled="busy||proposal.length>0||!group||!shapes.length||draft.length>0">Publicar trazado revisado</x-filament::button></footer>
</div>
</x-filament-panels::page>
