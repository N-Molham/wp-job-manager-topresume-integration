/**
 * Created by Nabeel on 2016-02-02.
 */
!function(a,b,c){b(function(){var a=b("#submit-resume-form"),c=b("#candidate_name"),d=b("#candidate_first_name, #candidate_last_name");d.on("change keyup wpjm-tri-change",function(){
// $('#candidate_name')
c.val(d.filter("#candidate_first_name").val()+" "+d.filter("#candidate_last_name").val()),console.log(c.val())}).first().trigger("wpjm-tri-change"),b(".resume-manager-add-row").on("click wpjm-click",function(a){setTimeout(function(){b("#submit-resume-form").find(".resume-manager-data-row .jmfe-date-picker:not(.hasDatepicker)").each(function(a,c){b(c).datepicker(jmfe_date_field)})},100)}),a.find(".fieldset-candidate_education, .fieldset-candidate_experience").on("change",".jmfe-date-picker",function(a){var c=b(a.currentTarget).closest(".resume-manager-data-row");c.find(".fieldset-date input").val(c.find(".fieldset-start_date input").val()+" / "+c.find(".fieldset-end_date input").val())})})}(window,jQuery);