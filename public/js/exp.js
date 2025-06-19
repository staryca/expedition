const sendRequest = function (url, method, data, title, id, index, errorMessage) {
    return new Promise(function (resolve) {
        const xhr = new XMLHttpRequest()
        xhr.open(method, url, true)
        if (method !== 'DELETE') {
            xhr.setRequestHeader("Content-type", method === 'POST' ? "application/ld+json" : "application/merge-patch+json")
        }
        xhr.onreadystatechange = () => {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                const status = xhr.status;
                let obj = {}
                if (xhr.getResponseHeader("content-type")
                    && xhr.getResponseHeader("content-type").indexOf('json') > 0
                    && xhr.responseText
                    && xhr.responseText.length > 2
                ) {
                    obj = JSON.parse(xhr.responseText)
                }
                if (obj.id === undefined) {
                    obj.id = id
                }
                if (status === 0 || (status >= 200 && status < 400)) {
                    obj.status = status
                    obj.isNew = method === 'POST'
                    obj.index = index
                    resolve(obj)
                } else {
                    let message = errorMessage === undefined ? 'Даныя не захаваліся!' : errorMessage
                    if (obj.description !== undefined) {
                        message += '</br>' + status + '. ' + obj.description
                    }
                    showMessage(status, message, title, '#' + id)
                }
            }
        };
        xhr.send(JSON.stringify(data))
    })
}

// save for actions on the page
const reportSaveAction = document.getElementById('reportSaveAction')
if (reportSaveAction) {
    if (document.forms['reportEdit'].id.value !== '') {
        showSubReportBlocks()
    }

    reportSaveAction.addEventListener('click', () => {

        const form = document.getElementById("reportEdit")
        const formData = new FormData(form)
        const formDataObj = Object.fromEntries(formData.entries())
        let id = formDataObj.id
        const isNew = id === ''
        delete formDataObj.id
        if (isNew) {
            formDataObj.dateCreated = (new Date()).toISOString()
        }
        if (formDataObj.geoPoint === '') {
            formDataObj.geoPoint = null
        }

        sendRequest(
            window.location.origin + '/api/reports' + (isNew ? '' : '/' + id),
            isNew ? 'POST' : 'PATCH',
            formDataObj,
            'Справаздача',
            id,
            null
        ).then(function (obj) {
            showMessage(obj.status, 'Даныя захаваліся паспяхова!', 'Справаздача', '#' + obj.id)
            if (obj.isNew) {
                document.forms['reportEdit'].id.value = obj.id
                showSubReportBlocks()
                window.location.replace('/report/' + obj.id + '/edit')
            }
        })
    })
}

function showSubReportBlocks() {
    const collection = document.getElementsByClassName('showThisAfterSaveReport')
    for (let i = 0; i < collection.length; i++) {
        collection[i].classList.remove('d-none')
    }
}

const allEditReportBlocks = document.getElementsByClassName("edit-report-block");
for (let i = 0; i < allEditReportBlocks.length; i++) {
    addActionBlock(allEditReportBlocks[i].getAttribute('data-index'), true)
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
        addActionBlock(i, false)
        document.getElementById("blockType" + i).focus()
    })
}

function addActionBlock(index, savedBlock) {
    if (savedBlock && index !== 'NUMBERBLOCK') {
        showSubBlocks(index)
    }

    document.getElementById('editReportBlock' + index + 'SaveAction').addEventListener('click', () => {

        const form = document.getElementById("blockEdit" + index)
        const formData = new FormData(form)
        const formDataObj = Object.fromEntries(formData.entries())
        let id = formDataObj.id
        const isNew = id === ''
        formDataObj.type = parseInt(formDataObj.type)
        delete formDataObj.id
        delete formDataObj.file
        delete formDataObj.informants
        if (isNew) {
            formDataObj.dateCreated = (new Date()).toISOString()
        }
        if (formDataObj.organization === '') {
            formDataObj.organization = null
        }

        sendRequest(
            window.location.origin + '/api/report_blocks' + (isNew ? '' : '/' + id),
            isNew ? 'POST' : 'PATCH',
            formDataObj,
            'Блок ' + index,
            id,
            index
        ).then(function (obj) {
            if (obj.isNew) {
                document.forms["blockEdit" + obj.index].id.value = obj.id
                showSubBlocks(obj.index)
                createContentFile(obj.index, obj.id)
            }
            showMessage(obj.status, 'Даныя захаваліся паспяхова!', 'Блок ' + obj.index, '#' + obj.id)
        })
    })
}

function showSubBlocks(index) {
    const collection = document.getElementsByClassName('showThisAfterSaveBlock' + index)
    for (let i = 0; i < collection.length; i++) {
        collection[i].classList.remove('d-none')
    }
}

function createContentFile(index, reportBlockId) {
    const formDataObj = {reportBlock: '/api/report_blocks/' + reportBlockId, type: 0, processed: false, subject: null}

    sendRequest(
        window.location.origin + '/api/files',
        'POST',
        formDataObj,
        'Блок ' + index,
        null,
        index,
        'Файл кантэнта не стварыўся!'
    ).then(function (obj) {
        document.forms["blockEdit" + obj.index].file.value = obj.id
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

        let reportBlockInput = addSubjectModal.querySelector('input[name="reportBlock"]')
        reportBlockInput.value = '/api/report_blocks/' + document.forms["blockEdit" + blockIndex].id.value
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

        let reportInput = addTaskPlanModal.querySelector('input[name="report"]')
        reportInput.value = blockIndex === '0' ? '/api/reports/' + document.forms['reportEdit'].id.value : ''

        let reportBlockInput = addTaskPlanModal.querySelector('input[name="reportBlock"]')
        reportBlockInput.value = blockIndex === '0' ? '' : '/api/report_blocks/' + document.forms["blockEdit" + blockIndex].id.value

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
