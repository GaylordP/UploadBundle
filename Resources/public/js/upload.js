window.Dropzone = require('dropzone')
Dropzone.autoDiscover = false

global.Upload = class {
    constructor(form_id, successCallback) {
        let previewTemplate = $('#upload-template').html()
        let $container = $('#dropzone-' + form_id)
        let $form = $container.closest('form');

        this.dropzone = new Dropzone('div#dropzone-' + form_id, {
            url: '/upload',
            chunking: true,
            chunkSize: 8000000,
            retryChunks: true,
            maxFilesize: parseInt($container.attr('data-constraint-maxsize-binary')) / 1000000,
            acceptedFiles: $container.attr('data-constraint-mime'),
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
            dictResponseError: UPLOAD_TRANSLATION['Error'] + ' : Code {{statusCode}}.',
            dictFileSizeUnits: {
                tb: UPLOAD_TRANSLATION['binary.tb'],
                gb: UPLOAD_TRANSLATION['binary.gb'],
                mb: UPLOAD_TRANSLATION['binary.mb'],
                kb: UPLOAD_TRANSLATION['binary.kb'],
                b: UPLOAD_TRANSLATION['binary.b'],
            },
            headers: {
                'form-controller': $container.attr('data-controller'),
                'form-upload-name': $container.attr('data-form-name'),
            },
            init: function() {
                this.on('addedfile', (file) => {
                    let $previewElement = $(file.previewElement)
                    let $status = $previewElement.find('span[data-dz-status]')

                    $status.addClass('btn-warning')
                    $status.text(UPLOAD_TRANSLATION['label.in_progress'])
                })

                this.on('sending', (data, xhr, formData) => {
                    formData.append('form-data', $form.serialize())
                })

                this.on('success', (file, response) => {
                    let $previewElement = $(file.previewElement)
                    let $status = $previewElement.find('span[data-dz-status]')
                    let $progressBar = $previewElement.find('.progress-bar')

                    $status.removeClass('btn-warning')
                    $status.addClass('btn-success')
                    $status.text(UPLOAD_TRANSLATION['label.finished'])
                    $progressBar.attr('aria-valuenow', '100')
                    $progressBar.removeClass('progress-bar-striped')
                    $progressBar.removeClass('progress-bar-animated')

                    if (successCallback !== undefined) {
                        successCallback($.parseJSON(file.xhr.response))
                    }
                })

                this.on('error', (file, errorMessage) => {
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
