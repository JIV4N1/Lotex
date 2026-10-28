<x-filament-panels::page>
<div x-data="{
 proposal:[], cropX:0, cropY:0, cropWidth:0, cropHeight:0, group:'', lots:[], shapes:[], draft:[], selected:'', image:null, width:1200, height:800, zoom:1, preview:false, message:'', busy:false,
 async load(){ this.proposal=[]; this.message=''; try { const data=await $wire.loadMap(Number(this.group)); this.lots=data.lots; this.shapes=data.map?.lots||[]; this.width=data.map?.width||1200; this.height=data.map?.height||800; this.draft=[]; this.selected=''; this.image=null; }catch(e){this.message='No se pudo cargar la lotificación.';} },
 upload(event){const file=event.target.files[0]; if(!file)return; if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>20000000){this.message='Selecciona JPG, PNG o WebP de hasta 20 MB.';return;} const url=URL.createObjectURL(file);const img=new Image();img.onload=()=>{if(img.width>10000||img.height>10000){URL.revokeObjectURL(url);this.message='La imagen excede 10000 píxeles.';return;} if(this.shapes.length&amp;&amp;(this.width!==img.width||this.height!==img.height)){URL.revokeObjectURL(url);this.message='La guía debe tener las mismas dimensiones que el trazado guardado.';return;}if(this.image)URL.revokeObjectURL(this.image);this.image=url;this.width=img.width;this.height=img.height;this.cropX=0;this.cropY=0;this.cropWidth=img.width;this.cropHeight=img.height;this.proposal=[];};img.src=url;},
 add(event){if(this.preview||!this.selected||!this.image)return;const p=new DOMPoint(event.clientX,event.clientY).matrixTransform(this.$refs.canvas.getScreenCTM().inverse());this.draft.push([Math.round(p.x),Math.round(p.y)]);},
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
<p>Traza cada división sobre la guía y selecciona el lote correspondiente. Los nombres y superficies proceden de los registros del sistema. Puedes trazar manualmente o pedir una propuesta a Gemini. Al analizar se envía a Google únicamente el recorte seleccionado, que puede contener información del plano. La guía no aparece en el mapa público. Las medidas registradas no se modifican.</p>
<div style="display:flex;gap:16px;flex-wrap:wrap;margin:20px 0">
<label>Lotificación <select x-model="group" @change="load()"><option value="">Selecciona</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->title }}</option>@endforeach</select></label>
<label>Plano guía <input type="file" accept="image/png,image/jpeg,image/webp" @change="upload($event)" :disabled="!group"></label>
<label>Lote <select x-model="selected" @change="draft=[]"><option value="">Selecciona</option><template x-for="lot in lots" :key="lot.id"><option :value="lot.id" x-text="name(lot.id)"></option></template></select></label>
<label>Zoom <input type="range" min="0.25" max="3" step="0.25" x-model="zoom"></label>
<label><input type="checkbox" x-model="preview"> Vista limpia</label>
</div>
<fieldset style="margin:16px 0"><legend>Recorte para Gemini (píxeles de la imagen)</legend>
<label>X <input type="number" min="0" x-model="cropX" style="width:90px"></label>
<label>Y <input type="number" min="0" x-model="cropY" style="width:90px"></label>
<label>Ancho <input type="number" min="1" x-model="cropWidth" style="width:90px"></label>
<label>Alto <input type="number" min="1" x-model="cropHeight" style="width:90px"></label>
<x-filament::button type="button" @click="detect()" x-bind:disabled="busy||!image||!group">Detectar lotes con Gemini</x-filament::button>
<x-filament::button type="button" @click="acceptProposal()" x-bind:disabled="busy||!proposal.length">Incorporar propuesta</x-filament::button>
<x-filament::button type="button" @click="proposal=[]" x-bind:disabled="busy||!proposal.length">Descartar propuesta</x-filament::button>
</fieldset>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px">
<x-filament::button type="button" @click="draft.pop()">Deshacer punto</x-filament::button>
<x-filament::button type="button" @click="finish()" x-bind:disabled="draft.length&lt;3">Cerrar lote</x-filament::button>
<x-filament::button type="button" @click="shapes=shapes.filter(s=>s.id!==Number(selected));draft=[]" color="danger">Quitar trazo seleccionado</x-filament::button>
<x-filament::button type="button" @click="save()" x-bind:disabled="busy||proposal.length>0||!group||!shapes.length||draft.length>0">Publicar trazado revisado</x-filament::button>
</div>
<template x-if="lots.find(l=>l.id===Number(selected))"><p x-text="'Frente: '+(lots.find(l=>l.id===Number(selected)).frontage_m||'Por confirmar')+' m · Fondo: '+(lots.find(l=>l.id===Number(selected)).depth_m||'Por confirmar')+' m'"></p></template>
<p role="status" x-text="message"></p><p x-text="shapes.length+' lotes trazados de '+lots.length"></p>
<div style="overflow:auto;max-height:70vh;border:1px solid #888;background:white" wire:ignore>
<svg x-ref="canvas" :viewBox="'0 0 '+width+' '+height" :style="'width:'+width*zoom+'px;max-width:none;display:block'" @click="add($event)" aria-label="Área de trazado: pulsa en cada esquina del lote">
<image :href="image||''" :width="width" :height="height" x-show="image&amp;&amp;!preview" opacity="0.65" />
<template x-for="shape in shapes" :key="shape.id"><g><polygon :points="points(shape)" fill="#245440" :fill-opacity="preview?0.15:0.12" stroke="#245440" stroke-width="2"/><text :x="center(shape,0)" :y="center(shape,1)" font-size="14" text-anchor="middle" fill="#102e24" x-text="name(shape.id)"></text></g></template>
<template x-for="shape in proposal" :key="shape.id"><g><polygon :points="points(shape)" fill="#f59e0b" fill-opacity="0.25" stroke="#b45309" stroke-width="3"/><text :x="center(shape,0)" :y="center(shape,1)" font-size="14" text-anchor="middle" x-text="name(shape.id)"></text></g></template>
<rect :x="cropX" :y="cropY" :width="cropWidth" :height="cropHeight" fill="none" stroke="#2563eb" stroke-width="2" stroke-dasharray="10 6" x-show="!preview" pointer-events="none"/>
<polyline :points="draft.map(p=>p.join(',')).join(' ')" fill="none" stroke="#dc2626" stroke-width="3"/>
<template x-for="(point,index) in draft" :key="index"><circle :cx="point[0]" :cy="point[1]" r="4" fill="#dc2626"/></template>
</svg></div>
</div>
</x-filament-panels::page>
