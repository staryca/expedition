// save for actions on the page
const reportSaveAction = document.getElementById('reportSaveAction')
if (reportSaveAction) {
    reportSaveAction.addEventListener('click', () => {

        const form = document.getElementById("reportEdit");
        const formData = new FormData(form);
        const formDataObj = Object.fromEntries(formData.entries());
        let id = formDataObj.id
        const isNew = id === ''
        delete formDataObj.id
        if (isNew) {
            formDataObj.dateCreated = (new Date()).toISOString()
        }
        if (formDataObj.geoPoint === '') {
            formDataObj.geoPoint = null
        }

        const xhr = new XMLHttpRequest();
        const method = isNew ? 'POST' : 'PATCH';
        const url = window.location.origin + '/api/reports' + (isNew ? '' : '/' + id);

        xhr.open(method, url, true);
        xhr.setRequestHeader("Content-type", isNew ? "application/ld+json" : "application/merge-patch+json");
        xhr.onreadystatechange = () => {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                const status = xhr.status;
                if (status === 0 || (status >= 200 && status < 400)) {
                    if (isNew) {
                        let obj = JSON.parse(xhr.responseText)
                        id = obj.id
                        document.forms['reportEdit'].id.value = id
                    }
                    showMessage(status, 'Данныя захаваліся паспяхова!', 'Справаздача', '#' + id)
                } else {
                    let message = 'Данныя не захаваліся!'
                    if (xhr.getResponseHeader("content-type").indexOf('json') > 0) {
                        let obj = JSON.parse(xhr.responseText)
                        message += '</br>' + status + '. ' + obj.description
                    }
                    showMessage(status, message, 'Справаздача', '#' + id)
                }
            }
        };
        xhr.send(JSON.stringify(formDataObj));
    })
}

const allEditReportBlocks = document.getElementsByClassName("edit-report-block");
for (let i = 0; i < allEditReportBlocks.length; i++) {
    const index = allEditReportBlocks[i].getAttribute('data-index')
    document.getElementById('editReportBlock' + index + 'SaveAction').addEventListener('click', () => {

        const form = document.getElementById("blockEdit" + index);
        const formData = new FormData(form);
        const formDataObj = Object.fromEntries(formData.entries());
        let id = formDataObj.id
        const isNew = id === ''
        formDataObj.type = parseInt(formDataObj.type)
        delete formDataObj.id
        if (isNew) {
            formDataObj.dateCreated = (new Date()).toISOString()
        }
        if (formDataObj.organization === '') {
            formDataObj.organization = null
        }

        const xhr = new XMLHttpRequest();
        const method = isNew ? 'POST' : 'PATCH';
        const url = window.location.origin + '/api/report_blocks' + (isNew ? '' : '/' + id);

        xhr.open(method, url, true);
        xhr.setRequestHeader("Content-type", isNew ? "application/ld+json" : "application/merge-patch+json");
        xhr.onreadystatechange = () => {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                const status = xhr.status;
                if (status === 0 || (status >= 200 && status < 400)) {
                    if (isNew) {
                        let obj = JSON.parse(xhr.responseText)
                        id = obj.id
                        document.forms["blockEdit" + index].id.value = id
                    }
                    showMessage(status, 'Данныя захаваліся паспяхова!', 'Блок ' + index, '#' + id)
                } else {
                    let message = 'Данныя не захаваліся!'
                    if (xhr.getResponseHeader("content-type").indexOf('json') > 0) {
                        let obj = JSON.parse(xhr.responseText)
                        message += '</br>' + status + '. ' + obj.description
                    }
                    showMessage(status, message, 'Блок ' + index, '#' + id)
                }
            }
        };
        xhr.send(JSON.stringify(formDataObj));
    })
}

// create new block
const createNewBlock = document.getElementById('createNewBlock')
if (createNewBlock) {
    createNewBlock.addEventListener('click', event => {
        let i = 1;
        let element = document.getElementById('block' + i + 'body')
        while (element) {
            i = i + 1
            element = document.getElementById('block' + i + 'body')
        }

        let html = document.getElementById('blockMenuTemplate').innerHTML
        html = html.replaceAll('NUMBERBLOCK', '' + i)
        let menuElement = document.getElementById('createNewBlock')
        menuElement.insertAdjacentHTML('beforebegin', html)

        html = document.getElementById('createNewBlockTemplate').innerHTML
        html = html.replaceAll('NUMBERBLOCK', '' + i)
        let mainElement = document.getElementById('mainBlock')
        mainElement.insertAdjacentHTML('beforeend', html)
    })
}

// Modal functions
const addSubjectModal = document.getElementById('addSubjectModal')
if (addSubjectModal) {
    addSubjectModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget
        const blockIndex = button.getAttribute('data-bs-block')

        let addSubjectModalLabel = document.getElementById('addSubjectModalLabel')
        addSubjectModalLabel.innerText = 'Дадаць новы прадмет для блока ' + blockIndex

        let blockIndexInput = addSubjectModal.querySelector('input[name="blockIndex"]')
        blockIndexInput.value = blockIndex
    })
}

const addEpisodeModal = document.getElementById('addEpisodeModal')
if (addEpisodeModal) {
    addEpisodeModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget
        const blockIndex = button.getAttribute('data-bs-block')

        let addSubjectModalLabel = document.getElementById('addEpisodeModalLabel')
        addSubjectModalLabel.innerText = 'Дадаць новы эпізод для блока ' + blockIndex

        let blockIndexInput = addEpisodeModal.querySelector('input[name="blockIndex"]')
        blockIndexInput.value = blockIndex
    })
}

const addInformantModal = document.getElementById('addInformantModal')
if (addInformantModal) {
    addInformantModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget
        const blockIndex = button.getAttribute('data-bs-block')

        let addInformantModalLabel = document.getElementById('addInformantModalLabel')
        addInformantModalLabel.innerText = 'Дадаць новага інфарманта для блока ' + blockIndex

        let blockIndexInput = addInformantModal.querySelector('input[name="blockIndex"]')
        blockIndexInput.value = blockIndex
    })
}

const addTaskPlanModal = document.getElementById('addTaskPlanModal')
if (addTaskPlanModal) {
    addTaskPlanModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget
        const blockIndex = button.getAttribute('data-bs-block')

        let blockIndexInput = addTaskPlanModal.querySelector('input[name="blockIndex"]')
        blockIndexInput.value = blockIndex

        let text = blockIndex === '0' ? 'справаздачы' : 'блока ' + blockIndex

        let addTaskPlanModalLabel = document.getElementById('addTaskPlanModalLabel')
        addTaskPlanModalLabel.innerText = 'Дадаць новую задачу для ' + text
    })
}

const addOrgModal = document.getElementById('addOrgModal')
if (addOrgModal) {
    addOrgModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget
        const blockIndex = button.getAttribute('data-bs-block')

        let addOrgModalLabel = document.getElementById('addOrgModalLabel')
        addOrgModalLabel.innerText = 'Дадаць новую арганізацыю для блока ' + blockIndex

        let blockIndexInput = addOrgModal.querySelector('input[name="blockIndex"]')
        blockIndexInput.value = blockIndex
    })
}

// Base functions
function showMessage(code, message, title, subtitle) {
    const uuid = self.crypto.randomUUID()
    let html = document.getElementById('toastBlockResult').outerHTML

    html = html.replace('toastBlockResult', 'toastBlockResult' + uuid)
    const toastBlockResultMessageId = 'toastBlockResultMessage' + uuid
    html = html.replace('toastBlockResultMessage', toastBlockResultMessageId)
    html = html.replace('toastBlockResultTitle', 'toastBlockResultTitle' + uuid)
    html = html.replace('toastBlockResultSubTitle', 'toastBlockResultSubTitle' + uuid)

    let messagesElement = document.getElementById('toastBlockMessages')
    messagesElement.insertAdjacentHTML('beforeend', html)

    const toastBlockResultMessageElement = document.getElementById(toastBlockResultMessageId)
    toastBlockResultMessageElement.classList.remove('text-bg-success')
    toastBlockResultMessageElement.classList.remove('text-bg-danger')
    toastBlockResultMessageElement.classList.add(code < 400 ? 'text-bg-success' : 'text-bg-danger')
    toastBlockResultMessageElement.innerHTML = message
    document.getElementById('toastBlockResultTitle' + uuid).innerText = title
    document.getElementById('toastBlockResultSubTitle' + uuid).innerText = subtitle

    bootstrap.Toast.getOrCreateInstance(
        document.getElementById('toastBlockResult' + uuid)
    ).show()
}
