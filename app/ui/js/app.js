$(function () {

    // datepicker
    $('.datepicker').datepicker({
        format: 'dd.mm.yyyy',
        weekStart: 1
    });


    // tag multiselect and auto-complete
    if($("#input-tags").length > 0) {

        var tags = $("#input-tags").tagsManager({
            tagsContainer: '#taglist',
            prefilled: $("#input-tags").attr('data-value').split(',')
        });

        $("#input-tags").typeahead({
            name: 'tags',
            limit: 150,
            prefetch: {
                url:'tag',
                ttl: 0
            }
        }).on('typeahead:selected', function (e, d) {
            tags.tagsManager("pushTag", d.value);
        });
    }


    // tooltips
    $('.bs-tooltip').tooltip();


    // editor
    if ($('#blog-text').length > 0) {
        $('#blog-text').summernote({
            height: 450
        });
        $('#post-form').on('submit', function () {
            $('#input-text').val($('#blog-text').code());
        });
    }


    // file upload
    if($('#fileupload').length > 0) {
        $('#fileupload').fileupload({
            url: 'admin/file',
            dataType: 'json',
            done: function (e, data) {
//                console.log(data);
                if ("error" in data._response.result) {
                    alert(data._response.result.error);
                } else {
                    for (file in data._response.result) {
                        if(data._response.result[file] == true) {
                            $('#post-image').remove();
                            var image = $('<img id="post-image" src="'+file+'" class="thumbnail" />');
                            image.hide().slideDown();
                            $('#upload-btn').before(image);
                            $('#input-image').val(file);
                        } else {
                            alert('Error uploading file.')
                        }
                        break;
                    }
                }
            }
        });
    }

});

