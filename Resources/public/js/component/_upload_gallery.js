const FormUploadGalleryBorder = (dom) => {
    if (dom === undefined) {
        dom = document
    }

    let entries = dom.querySelectorAll('.form-upload-gallery-entry')

    entries.forEach((entry) => {
        let input = entry.querySelector('input[type="checkbox"], input[type="radio"]')

        FormUploadGalleryEntryBorder(entry, input)
    })
}

const FormUploadGalleryEntryBorder = (entry, input) => {
    let thumbnail = entry.querySelector('.img-thumbnail')

    if (true === input.checked) {
        thumbnail.classList.add('bg-success', 'border-success')
    } else {
        thumbnail.classList.remove('bg-success', 'border-success')
    }
}

FormUploadGalleryBorder()

document.addEventListener('click', (e) => {
    let entry = e.target.closest('.form-upload-gallery-entry')

    if (null !== entry) {
        e.preventDefault()
        let gallery = entry.closest('.form-upload-gallery')
        let input = entry.querySelector('input[type="checkbox"], input[type="radio"]')

        if (true === input.checked) {
            input.checked = false
        } else {
            input.checked = true
        }

        FormUploadGalleryBorder(gallery)
    }
}, false)

export default FormUploadGalleryBorder
