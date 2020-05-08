window.Dropzone = require('dropzone')
Dropzone.autoDiscover = false

export default class Upload {
    constructor(form_id) {
        let previewTemplate = $('#upload-template').html()
        let $container = $('#dropzone-' + form_id)
        let $form = $container.closest('form');

        this.dropzone = new Dropzone('div#dropzone-' + form_id, {
            url: '/upload',
            chunking: true,
            chunkSize: 8000000,
            retryChunks: true,
//            maxFilesize: parseInt($container.attr('data-constraint-maxsize-binary')) / 1000000,
  //          acceptedFiles: $container.attr('data-constraint-mime'),
            thumbnailWidth: 100,
            thumbnailHeight: 100,
            parallelUploads: 1,
            previewTemplate: previewTemplate,
            dictFallbackMessage: 'Your browser does not support drag n drop file uploads.',
            dictFallbackText: 'Please use the fallback form below to upload your files like in the olden days.',
            dictFileTooBig: UPLOAD_TRANSLATION['The file is too large. Allowed maximum size is {{ limit }} {{ suffix }}']
                .replace('{{ limit }}', $container.attr('data-constraint-maxsize'))
                .replace('k {{ suffix }}', UPLOAD_TRANSLATION['binary.kb'])
                .replace('K {{ suffix }}', UPLOAD_TRANSLATION['binary.kb'])
                .replace('m {{ suffix }}', UPLOAD_TRANSLATION['binary.mb'])
                .replace('M {{ suffix }}', UPLOAD_TRANSLATION['binary.mb'])
                .replace('g {{ suffix }}', UPLOAD_TRANSLATION['binary.gb'])
                .replace('G {{ suffix }}', UPLOAD_TRANSLATION['binary.gb'])
                .replace('t {{ suffix }}', UPLOAD_TRANSLATION['binary.tb'])
                .replace('T {{ suffix }}', UPLOAD_TRANSLATION['binary.tb'])
            ,
            dictInvalidFileType: UPLOAD_TRANSLATION['The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.']
                .replace(' ({{ type }})', '')
                .replace('{{ types }}', '« ' + $container.attr('data-constraint-mime')
                .replace(/,/g, ' ; ') + ' »')
            ,
//            dictResponseError: UPLOAD_TRANSLATION['label.in_progress'] + ' : Code {{statusCode}}.',
            dictFileSizeUnits: {
                tb: UPLOAD_TRANSLATION['binary.tb'],
                gb: UPLOAD_TRANSLATION['binary.gb'],
                mb: UPLOAD_TRANSLATION['binary.mb'],
                kb: UPLOAD_TRANSLATION['binary.kb'],
                b: UPLOAD_TRANSLATION['binary.b'],
            },
            headers: {
                'form-type': $container.attr('data-type'),
            },
            init: function() {
                let myDropzone = this
                let $input = $(myDropzone.element).find('#' + form_id)

                this.on('addedfile', function(file) {
                    let $previewElement = $(file.previewElement)
                    let $status = $previewElement.find('span[data-dz-status]')

                    $status.addClass('btn-warning')
                    $status.text(UPLOAD_TRANSLATION['label.in_progress'])
                })

                this.on('sending', function(data, xhr, formData) {
                    formData.append('form-data', $form.serialize())
                })

                this.on('success', function(file) {
                    try {
                        let responseJson = JSON.parse(file.xhr.responseText)
                        let $previewElement = $(file.previewElement)

                        if (true === responseJson.success) {
                            let inputValue = $input.val()
                            let newInputValue = []

                            if ('' !== inputValue) {
                                newInputValue = JSON.parse(inputValue)
                            }

                            newInputValue.push(responseJson.path)

                            let $status = $previewElement.find('span[data-dz-status]')
                            let $progressBar = $previewElement.find('.progress-bar')

                            $status.removeClass('btn-warning')
                            $status.addClass('btn-success')
                            $status.text(UPLOAD_TRANSLATION['label.finished'])
                            $progressBar.attr('aria-valuenow', '100')
                            $progressBar.removeClass('progress-bar-striped')
                            $progressBar.removeClass('progress-bar-animated')

                            $input.val(JSON.stringify(newInputValue))
                        } else {
                            myDropzone.emit('error', file, responseJson.message)
                        }
                    } catch (error) {
                        console.log(error)
                    }
                })

                this.on('error', function(file, errorMessage) {
                    let $previewElement = $(file.previewElement)
                    let $invalidFeedback = $previewElement.find('p.invalid-feedback')
                    let $formErrorIcon = $invalidFeedback.find('span.form-error-icon')
                    let $status = $previewElement.find('span[data-dz-status]')
                    let $progressBar = $previewElement.find('.progress-bar')

                    $invalidFeedback.removeClass('d-hide')
                    $invalidFeedback.addClass('d-block')
                    $formErrorIcon.text(UPLOAD_TRANSLATION['Error'])
                    $status.removeClass('btn-warning')
                    $status.addClass('btn-danger')
                    $status.text(UPLOAD_TRANSLATION['Error'])
                    $progressBar.removeClass('bg-success')
                    $progressBar.addClass('bg-danger')
                    $progressBar.removeClass('progress-bar-animated')
                    $progressBar.attr('aria-valuenow', '100')
                    $progressBar.css('width', '100%')
                })
            }
        })
    }
}
