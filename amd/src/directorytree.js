import $ from 'jquery';
import 'assignsubmission_noto/jstree';
import ajax from 'core/ajax';
export const init = (courseid, apinotebookpath, redirecttonoto_string) => {
    $(function () {
        var promises = ajax.call([
            { methodname: 'assignsubmission_noto_get_jstree_json', args: { courseid: courseid} },
        ]);
        promises[0].done(function(response) {
            if (response.warnings.length > 0) {
                let warnings = '';
                response.warnings.every(function(w) {
                    warnings += w.warningcode + ': '+ w.message + '</br>';
                });
                $('#jstree').html(warnings);
            } else {
                var src = jQuery.parseJSON(response.result);
                $('#jstree').jstree({ 'core' : {
                        'data' : src
                    }});
                $("#assignsubmission_noto_directory").val("");
                $("#assignsubmission_noto_directory_h").val("");
                $('#jstree').on("changed.jstree", function (e, data) {
                    $("#assignsubmission_noto_directory").val(data.selected);
                    $("#assignsubmission_noto_path").html(data.selected);
                    $("#assignsubmission_noto_redirect").html('<a href="' + apinotebookpath + data.selected + '" target="_blank">'+redirecttonoto_string+'</a>');
                    $("#assignsubmission_noto_directory_h").val(data.selected);
                });
            }
        }).fail(function(ex) {
            console.log(ex);
            // do something with the exception
        });

        $("#assignsubmission_noto_reloadtree_submit").on('click', function(e) {
            e.preventDefault();
            var refreshpromises = ajax.call([
                { methodname: 'assignsubmission_noto_get_jstree_json', args: { courseid: courseid} },
            ]);
            refreshpromises[0].done(function(response) {
                var src = jQuery.parseJSON(response.result);
                if (src) {
                    $('#jstree').jstree(true).settings.core.data = src;
                    $('#jstree').jstree(true).refresh();
                }
            }).fail(function(ex) {
                console.log(ex);
                // do something with the exception
            });
        });
    });
};
