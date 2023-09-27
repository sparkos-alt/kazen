import{C as $}from"./Index.81946320.js";import{C as b}from"./Blur.8cc39c73.js";import{C as y}from"./Card.9b0c1a15.js";import{C as k}from"./Table.1ce53c08.js";import{r as n,o as l,c as m,b as d,w as o,d as s,f as _,g as p,t as h,n as x}from"./vue.runtime.esm-bundler.b39e1078.js";import{_ as g}from"./_plugin-vue_export-helper.b97bdf23.js";import{C as S}from"./Index.f7bbb33f.js";import{R as v}from"./RequiredPlans.fd5cb1f6.js";const w={components:{CoreAddRedirection:$,CoreBlur:b,CoreCard:y,CoreWpTable:k},props:{noCoreCard:Boolean},data(){return{strings:{addNewRedirection:this.$t.__("Add New Redirection",this.$td),searchUrls:this.$t.__("Search URLs",this.$td)},bulkOptions:[{label:"",value:""}]}},computed:{columns(){return[{slug:"source_url",label:this.$t.__("Source URL",this.$td)},{slug:"target_url",label:this.$t.__("Target URL",this.$td)},{slug:"hits",label:this.$t.__("Hits",this.$td),width:"97px"},{slug:"type",label:this.$t.__("Type",this.$td),width:"100px"},{slug:"group",label:this.$t.__("Group",this.$td),width:"150px"},{slug:"enabled",label:this.$constants.GLOBAL_STRINGS.enabled,width:"80px"}]},additionalFilters(){return[{label:this.$t.__("Filter by Group",this.$td),name:"group",options:[{label:this.$t.__("All Groups",this.$td),value:"all"}].concat(this.$constants.REDIRECT_GROUPS)}]}}},A={class:"aioseo-redirects-blur"};function E(r,C,e,R,t,c){const a=n("core-add-redirection"),i=n("core-blur"),u=n("core-card"),f=n("core-wp-table");return l(),m("div",A,[e.noCoreCard?_("",!0):(l(),d(u,{key:0,slug:"addNewRedirection","header-text":t.strings.addNewRedirection,noSlide:!0},{default:o(()=>[s(i,null,{default:o(()=>[s(a,{type:r.$constants.REDIRECT_TYPES[0].value,query:r.$constants.REDIRECT_QUERY_PARAMS[0].value,slash:!0,case:!0},null,8,["type","query"])]),_:1})]),_:1},8,["header-text"])),e.noCoreCard?(l(),d(i,{key:1},{default:o(()=>[s(a,{type:r.$constants.REDIRECT_TYPES[0].value,query:r.$constants.REDIRECT_QUERY_PARAMS[0].value,slash:!0,case:!0},null,8,["type","query"])]),_:1})):_("",!0),s(i,null,{default:o(()=>[s(f,{filters:[],totals:{total:0,pages:0,page:1},columns:c.columns,rows:[],"search-label":t.strings.searchUrls,"bulk-options":t.bulkOptions,"additional-filters":c.additionalFilters},null,8,["columns","search-label","bulk-options","additional-filters"])]),_:1})])}const U=g(w,[["render",E]]),T={components:{Blur:U,RequiredPlans:v,Cta:S},props:{noCoreCard:Boolean,parentComponentContext:String},data(){return{strings:{ctaButtonText:this.$t.__("Upgrade to Pro and Unlock Redirects",this.$td),ctaHeader:this.$t.sprintf(this.$t.__("Redirects are only available for licensed %1$s %2$s users.",this.$td),"AIOSEO","Pro"),serverRedirects:this.$t.__("Fast Server Redirects",this.$td),automaticRedirects:this.$t.__("Automatic Redirects",this.$td),redirectMonitoring:this.$t.__("Redirect Monitoring",this.$td),monitoring404:this.$t.__("404 Monitoring",this.$td),fullSiteRedirects:this.$t.__("Full Site Redirects",this.$td),siteAliases:this.$t.__("Site Aliases",this.$td),redirectsDescription:this.$t.__("Our Redirection Manager allows you to easily create and manage redirects for your broken links to avoid confusing search engines and users, as well as losing valuable backlinks. It even automatically sends users and search engines from your old URLs to your new ones.",this.$td)}}}};function B(r,C,e,R,t,c){const a=n("blur"),i=n("required-plans"),u=n("cta");return l(),m("div",{class:x({"aioseo-redirects":!0,"core-card":!e.noCoreCard})},[s(a,{noCoreCard:e.noCoreCard},null,8,["noCoreCard"]),s(u,{"cta-link":r.$links.getPricingUrl("redirects","redirects-upsell",e.parentComponentContext?e.parentComponentContext:null),"button-text":t.strings.ctaButtonText,"learn-more-link":r.$links.getUpsellUrl("redirects",e.parentComponentContext?e.parentComponentContext:null,"home"),"feature-list":[t.strings.serverRedirects,t.strings.automaticRedirects,t.strings.redirectMonitoring,t.strings.monitoring404,t.strings.fullSiteRedirects,t.strings.siteAliases]},{"header-text":o(()=>[p(h(t.strings.ctaHeader),1)]),description:o(()=>[s(i,{addon:"aioseo-redirects"}),p(" "+h(t.strings.redirectsDescription),1)]),_:1},8,["cta-link","button-text","learn-more-link","feature-list"])],2)}const G=g(T,[["render",B]]);export{G as R};