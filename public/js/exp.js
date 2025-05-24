// save for actions on the page
const reportSaveAction = document.getElementById('reportSaveAction')
if (reportSaveAction) {
    reportSaveAction.addEventListener('click', () => {
        let id = document.getElementById('editReportId').value
        // todo: save report
        if (!id) id = '00000';
        showMessage(200, 'Данныя захаваліся паспяхова!', 'Справаздача', '#' + id)
    })
}

const addTaskPlanModal = document.getElementById('addTaskPlanModal')
if (addTaskPlanModal) {
    addTaskPlanModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget
        const type = button.getAttribute('data-type-modal')
        let text = type === 'report' ? 'справаздачы' : 'блока ' + button.getAttribute('data-block-index')

        // Update the modal's content.
        const notesElement = document.getElementById('addTaskPlanModalNotes')
        notesElement.textContent = 'Для ' + text
    })
}

const allEditReportBlocks = document.getElementsByClassName("edit-report-block");
for (let i = 0; i < allEditReportBlocks.length; i++) {
    const index = allEditReportBlocks[i].getAttribute('data-index')
    document.getElementById('editReportBlock' + index + 'SaveAction').addEventListener('click', () => {
        let id = document.getElementById('editReportBlock' + index).value
        // todo: save main block info
        if (!id) id = '00000';
        showMessage(200, 'Данныя захаваліся паспяхова!', 'Блок ' + index, '#' + id)
    })
}

const allBlockInformants = document.getElementsByClassName("edit-block-informant");
for (let i = 0; i < allBlockInformants.length; i++) {
    const id = allBlockInformants[i].getAttribute('data-index')
    const block = allBlockInformants[i].getAttribute('data-block')
    allBlockInformants[i].addEventListener('click', () => {
        // todo
        showMessage(400, 'Данныя выдалены паспяхова!', 'Інфармант', '#' + id)
        document.getElementById('editBlock' + block + 'Informant' + id).remove()
    })
}

const allBlockTasks = document.getElementsByClassName("edit-block-task");
for (let i = 0; i < allBlockTasks.length; i++) {
    const id = allBlockTasks[i].getAttribute('data-index')
    const block = allBlockTasks[i].getAttribute('data-block')
    allBlockTasks[i].addEventListener('click', () => {
        // todo
        showMessage(400, 'Данныя выдалены паспяхова!', 'Задача ці наводка блока', '#' + id)
        document.getElementById('editBlock' + block + 'Task' + id).remove()
    })
}

// save in dialogs
const saveReportTask = document.getElementById('saveReportTask')
if (saveReportTask) {
    saveReportTask.addEventListener('click', event => {
        const form = document.getElementById('formAddTaskPlan')
        if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
        } else {
            // todo
            const id = '00000'
            const status = form.querySelector('select[name="status"]').value
            const content = form.querySelector('textarea[name="content"]').value
            addReportTaskBlock(status, id, content, '')

            showMessage(200, 'Данныя захаваліся паспяхова!', 'План, задача, наводка', '#' + id)

            const modalElement = document.getElementById('addTaskPlanModal')
            bootstrap.Modal.getInstance(modalElement).hide()

            form.classList.remove('was-validated')
            form.reset()
        }
        form.classList.add('was-validated')
    })
}

const saveNewOrganization = document.getElementById('saveNewOrganization')
if (saveNewOrganization) {
    saveNewOrganization.addEventListener('click', event => {
        const form = document.getElementById('formAddOrg')
        if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
        } else {
            // todo
            const id = '00000'
            showMessage(200, 'Данныя захаваліся паспяхова!', 'Арганізацыя', '#' + id)

            const modalElement = document.getElementById('addOrgModal')
            bootstrap.Modal.getInstance(modalElement).hide()

            form.classList.remove('was-validated')
            form.reset()
        }
        form.classList.add('was-validated')
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
