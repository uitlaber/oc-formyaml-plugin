// function docCheck(input) {
//     $(input).parent('label').find('span').text('{{ 'file_checked'|_ }}');
// }

function loadFilePreview() {
    console.log(event.target.id);
    var id = event.target.id;
    var name = event.target.name;
    var uploadContainer = document.getElementById(id + '-uploads');
    //console.log(uploadContainer.dataset.multiple);
    var multiple = ("multiple" in uploadContainer.dataset) ? uploadContainer.dataset.multiple : 0;
    var hideDelete = ("hidedelete" in uploadContainer.dataset) ? uploadContainer.dataset.hidedelete : 0;
    var hideFilename = ("hidefilename" in uploadContainer.dataset) ? uploadContainer.dataset.hidefilename : 0;
    var mode = ("mode" in uploadContainer.dataset) ? uploadContainer.dataset.mode : 'file';


    if (event.target.files) {
        var files = event.target.files;
        var images = [];
        Array.prototype.forEach.call(files, function(file){
            var img = document.createElement('div');
            var span = document.createElement('span');
           
            
            var deleteBtn = document.createElement('a');
            deleteBtn.innerHTML  = '&#10006;';
            span.textContent = file.name;  
            console.log(hideFilename);
            console.log(hideDelete);
            if (parseInt(hideFilename)) {
                span.style.display = 'none';
            }
            if (parseInt(hideDelete)) {
                deleteBtn.style.display = 'none';
            }
            span.textContent = file.name;            
            img.appendChild(span);
            img.appendChild(deleteBtn);
         
            // var reader = new FileReader();
            // reader.onload = function (e) {
            //     img.style.backgroundImage = `url('${reader.result}')`;
            //     uploadContainer.appendChild(img);
            // };

            var formData = new FormData();
            formData.append("file", file);
            formData.append("type", mode);

            $.ajax({
                headers:
                    {
                        'X-OCTOBER-REQUEST-HANDLER': 'onUpload', // important
                    },
                type: 'post',
                cache: false,
                contentType: false, // important
                processData: false,  // important
                data: formData,
                success: function(data)
                {
                    
                        var input = document.createElement("input");
                        input.setAttribute("type", "hidden");
                        input.setAttribute("name", name);
                        input.setAttribute("value", data.id);

                        img.appendChild(input);
                    if (mode === 'image')
                        img.style.backgroundImage = `url('${data.thumb}')`;
                    
                    deleteBtn.setAttribute('data-name',data.disk_name);

                   
                    if (multiple == 0) {
                        while (uploadContainer.firstChild) {
                            uploadContainer.removeChild(uploadContainer.firstChild);
                        }
                    }
                    uploadContainer.appendChild(img);
                }
            });
            
            deleteBtn.addEventListener("click", function (e) {
                e.preventDefault();
                var confirmDelete = confirm('DELETE_FILE?');
                var filesArr = Array.prototype.slice.call(files);
                if(confirmDelete) {
                    var formData = new FormData();
                    formData.append("name", deleteBtn.dataset.name);
                    $.ajax({
                        headers:
                            {
                                'X-OCTOBER-REQUEST-HANDLER': 'onDeleteFile', // important
                            },
                        type: 'post',
                        cache: false,
                        contentType: false, // important
                        processData: false,  // important
                        data:  formData,
                        success: function(data)
                        {
                            img.remove();
                        }
                    });
                }
                console.log(filesArr);
            }, false);
            // reader.readAsDataURL(file);
        });
    }
}
