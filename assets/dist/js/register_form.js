/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c,d){b(function(){var d=/([^&=]+)=?([^&]*)/g,e=/\+/g,f=function(a){return decodeURIComponent(a.replace(e," "))},g=function(a){for(var b,c={};b=d.exec(a);){var e=f(b[1]),g=f(b[2]);"[]"===e.substring(e.length-2)?(e=e.substring(0,e.length-2),(c[e]||(c[e]=[])).push(g)):c[e]=g}return c},h=b(c);h.ajaxSuccess(function(c,d,e,f){if("string"==typeof e.data){var h=g(e.data);h.hasOwnProperty("register")&&h.hasOwnProperty("reg_role")&&b(f).find(".woocommerce-error").length<1&&(a.location.href=wpjm_top_resume.post_resume_url)}}),h.on("submit",".modal form.register",function(a){a.preventDefault()})})}(window,jQuery,document);