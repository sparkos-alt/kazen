import{B as T}from"./Textarea.844f418e.js";import{C as k}from"./Blur.8cc39c73.js";import{C as S}from"./SettingsRow.1adac8e2.js";import{C as v}from"./Index.f7bbb33f.js";import{r as s,o as l,c as g,d as n,w as i,a as w,t as u,g as m,b as f,f as b}from"./vue.runtime.esm-bundler.b39e1078.js";import{_ as y}from"./_plugin-vue_export-helper.b97bdf23.js";import{c as B}from"./links.23796d97.js";import{B as A}from"./RadioToggle.334ba6b1.js";const j={components:{BaseTextarea:T,CoreBlur:k,CoreSettingsRow:S,Cta:v},props:{type:{type:String,required:!0},object:{type:Object,required:!0}},data(){return{strings:{customFields:this.$t.__("Custom Fields",this.$td),customFieldsDescription:this.$t.__("List of custom field names to include as post content for tags and the SEO Page Analysis. Add one per line.",this.$td),ctaDescription:this.$t.sprintf(this.$t.__("%1$s %2$s gives you advanced customizations for our page analysis feature, letting you add custom fields to analyze.",this.$td),"AIOSEO","Pro"),ctaButtonText:this.$t.__("Upgrade to Pro and Unlock Custom Fields",this.$td),ctaHeader:this.$t.sprintf(this.$t.__("Custom Fields are only available for licensed %1$s %2$s users.",this.$td),"AIOSEO","Pro")}}},methods:{getSchemaTypeOption(o){return this.schemaTypes.find(r=>r.value===o)}}},C={class:"aioseo-sa-ct-custom-fields lite"},O={class:"aioseo-description"};function P(o,r,t,_,e,p){const a=s("base-textarea"),c=s("core-settings-row"),h=s("core-blur"),d=s("cta");return l(),g("div",C,[n(h,null,{default:i(()=>[n(c,{name:e.strings.customFields,align:""},{content:i(()=>[n(a,{"min-height":200}),w("div",O,u(e.strings.customFieldsDescription),1)]),_:1},8,["name"])]),_:1}),n(d,{"cta-link":o.$links.getPricingUrl("custom-fields","custom-fields-upsell",`${t.object.name}-post-type`),"button-text":e.strings.ctaButtonText,"learn-more-link":o.$links.getUpsellUrl("custom-fields",t.object.name,"home")},{"header-text":i(()=>[m(u(e.strings.ctaHeader),1)]),description:i(()=>[m(u(e.strings.ctaDescription),1)]),_:1},8,["cta-link","button-text","learn-more-link"])])}const K=y(j,[["render",P]]);const U={components:{BaseRadioToggle:A,CoreBlur:k,CoreSettingsRow:S,Cta:v},props:{type:{type:String,required:!0},object:{type:Object,required:!0}},data(){return{schemaTypes:[{value:"none",label:this.$t.__("None",this.$td)},{value:"Article",label:this.$t.__("Article",this.$td)}],strings:{schemaType:this.$t.__("Schema Type",this.$td),articleType:this.$t.__("Article Type",this.$td),article:this.$t.__("Article",this.$td),blogPost:this.$t.__("Blog Post",this.$td),newsArticle:this.$t.__("News Article",this.$td),ctaDescription:this.$t.__("Easily generate unlimited schema markup for your content to help you rank higher in search results. Our schema validator ensures your schema works out of the box.",this.$td),ctaButtonText:this.$t.__("Upgrade to Pro and Unlock Schema Generator",this.$td),ctaHeader:this.$t.sprintf(this.$t.__("Schema Generator is only available for licensed %1$s %2$s users.",this.$td),"AIOSEO","Pro")},features:[this.$t.__("Unlimited Schema",this.$td),this.$t.__("Validate with Google",this.$td),this.$t.__("Increase Rankings",this.$td),this.$t.__("Additional Schema Types",this.$td)]}},methods:{getSchemaTypeOption(o){return this.schemaTypes.find(r=>r.value===o)}}},F={class:"aioseo-sa-ct-schema-lite"};function V(o,r,t,_,e,p){const a=s("base-select"),c=s("core-settings-row"),h=s("base-radio-toggle"),d=s("core-blur"),x=s("cta");return l(),g("div",F,[n(d,null,{default:i(()=>[n(c,{name:e.strings.schemaType,align:""},{content:i(()=>[n(a,{size:"medium",class:"schema-type",options:e.schemaTypes,modelValue:p.getSchemaTypeOption("Article")},null,8,["options","modelValue"])]),_:1},8,["name"]),n(c,{name:e.strings.articleType,align:""},{content:i(()=>[n(h,{name:`${t.object.name}articleType`,modelValue:"BlogPosting",options:[{label:e.strings.article,value:"Article"},{label:e.strings.blogPost,value:"BlogPosting"},{label:e.strings.newsArticle,value:"NewsArticle"}]},null,8,["name","options"])]),_:1},8,["name"])]),_:1}),n(x,{"cta-link":o.$links.getPricingUrl("schema-markup","schema-markup-upsell"),"button-text":e.strings.ctaButtonText,"learn-more-link":o.$links.getUpsellUrl("schema-markup",null,"home"),"feature-list":e.features},{"header-text":i(()=>[m(u(e.strings.ctaHeader),1)]),description:i(()=>[m(u(e.strings.ctaDescription),1)]),_:1},8,["cta-link","button-text","learn-more-link","feature-list"])])}const $=y(U,[["render",V]]),q={setup(){return{licenseStore:B()}},components:{Schema:$,SchemaLite:$},props:{type:{type:String,required:!0},object:{type:Object,required:!0},options:{type:Object,required:!0},showBulk:Boolean}},D={class:"aioseo-sa-ct-schema-view"};function N(o,r,t,_,e,p){const a=s("schema",!0),c=s("schema-lite");return l(),g("div",D,[_.licenseStore.isUnlicensed?b("",!0):(l(),f(a,{key:0,type:t.type,object:t.object,options:t.options,"show-bulk":t.showBulk},null,8,["type","object","options","show-bulk"])),_.licenseStore.isUnlicensed?(l(),f(c,{key:1,type:t.type,object:t.object,options:t.options,"show-bulk":t.showBulk},null,8,["type","object","options","show-bulk"])):b("",!0)])}const M=y(q,[["render",N]]);export{K as C,M as S};