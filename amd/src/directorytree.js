import $ from 'jquery';
import jtree from 'assignsubmission_noto/jstree';
export const init = (apinotebookpath, redirecttonoto_string) => {
    if ($ && jtree) {
        // do nothing, no.
    }
    $(function () {
        $('#jstree').jstree();
        $("#assignsubmission_noto_directory").val("");
        $("#assignsubmission_noto_directory_h").val("");
        $('#jstree').on("changed.jstree", function (e, data) {
            $("#assignsubmission_noto_directory").val(data.selected);
            $("#assignsubmission_noto_path").html(data.selected);
            $("#assignsubmission_noto_redirect").html('<a href="' + apinotebookpath + data.selected + '" target="_blank">'+redirecttonoto_string+'</a>');
            $("#assignsubmission_noto_directory_h").val(data.selected);
        });
    });
};
