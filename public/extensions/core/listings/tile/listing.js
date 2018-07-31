parcelRequire=function(e,r,n,t){var i="function"==typeof parcelRequire&&parcelRequire,o="function"==typeof require&&require;function u(n,t){if(!r[n]){if(!e[n]){var f="function"==typeof parcelRequire&&parcelRequire;if(!t&&f)return f(n,!0);if(i)return i(n,!0);if(o&&"string"==typeof n)return o(n);var c=new Error("Cannot find module '"+n+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[n][1][r]||r};var l=r[n]=new u.Module(n);e[n][0].call(l.exports,p,l,l.exports,this)}return r[n].exports;function p(e){return u(p.resolve(e))}}u.isParcelRequire=!0,u.Module=function(e){this.id=e,this.bundle=u,this.exports={}},u.modules=e,u.cache=r,u.parent=i,u.register=function(r,n){e[r]=[function(e,r){r.exports=n},{}]};for(var f=0;f<n.length;f++)u(n[f]);if(n.length){var c=u(n[n.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=c:"function"==typeof define&&define.amd?define(function(){return c}):t&&(this[t]=c)}return u}({"OSOB":[function(require,module,exports) {
module.exports={props:{primaryKeyField:{type:String,required:!0},fields:{type:Object,required:!0},items:{type:Array,default:function(){return[]}},viewOptions:{type:Object,default:function(){return{}}},viewQuery:{type:Object,default:function(){return{}}},loading:{type:Boolean,default:!1},lazyLoading:{type:Boolean,default:!1},selection:{type:Array,default:function(){return[]}},link:{type:String,default:null}}};
},{}],"7HHc":[function(require,module,exports) {
"use strict";Object.defineProperty(exports,"__esModule",{value:!0});var t=require("../../../mixins/listing"),e=i(t);function i(t){return t&&t.__esModule?t:{default:t}}exports.default={name:"listing-tile",mixins:[e.default],methods:{title:function(t){var e=this.viewOptions.title||this.primaryKeyField;return String(t[e])},subtitle:function(t){var e=this.viewOptions.subtitle||null;return e?t[e]?String(t[e]):"--":null},src:function(t){var e=this.viewOptions.src||null;return e?this.fields[e]&&"file"===this.fields[e].type.toLowerCase()?t[e]&&t[e].storage&&t[e].storage.full_url:"storage"===e&&"directus_files"===this.fields[e].collection?t[e]&&t[e].full_url:t[e]||null:null},content:function(t){var e=this.viewOptions.content||null;return e?t[e]||null:"--"},emptySrc:function(t){return null!=this.viewOptions.src&&null===this.src(t)},onScroll:function(t){var e=t.srcElement,i=e.scrollHeight,l=e.clientHeight,n=e.scrollTop;i-l-n<=500&&this.$emit("next-page"),this.scrolled=n>0}}};
(function(){var t=exports.default||module.exports;"function"==typeof t&&(t=t.options),Object.assign(t,{render:function(){var t=this,e=t.$createElement,o=t._self._c||e;return o("div",{staticClass:"listing-tile",on:{scroll:t.onScroll}},t._l(t.items,function(e){return o("v-card",{key:e.id,attrs:{to:e[t.link],title:t.title(e),subtitle:t.subtitle(e),icon:t.emptySrc(e)?t.viewOptions.icon||"photo":null,opacity:t.emptySrc(e)?"half":null,src:t.src(e),body:t.content(e)}},[t.lazyLoading?o("v-card",{attrs:{color:"dark-gray",icon:"hourglass_empty",opacity:"half",title:t.$t("loading_more")}}):t._e()],1)}))},staticRenderFns:[],_compiled:!0,_scopeId:"data-v-f44ab4",functional:void 0});})();
},{"../../../mixins/listing":"OSOB"}]},{},["7HHc"], "__DirectusExtension__")