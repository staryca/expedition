// save for actions on the page
const reportSaveAction = document.getElementById('reportSaveAction')
if (reportSaveAction) {
    reportSaveAction.addEventListener('click', () => {
        // todo
        const id = document.getElementById('editReportId').value
        showMessage(200, 'Данныя захаваліся паспяхова!', 'Справаздача', '#' + id)
    })
}

const allEditReportBlocks = document.getElementsByClassName("edit-report-block");
for (let i = 0; i < allEditReportBlocks.length; i++) {
    const index = allEditReportBlocks[i].getAttribute('data-index')
    document.getElementById('editReportBlock' + index + 'SaveAction').addEventListener('click', () => {
        // todo
        const id = document.getElementById('editReportBlock' + index).value
        showMessage(200, 'Данныя захаваліся паспяхова!', 'Блок ' + index, '#' + id)
    })
}

const allReportUserRoles = document.getElementsByClassName("report-user-role");
for (let i = 0; i < allReportUserRoles.length; i++) {
    const id = allReportUserRoles[i].getAttribute('data-index')
    allReportUserRoles[i].addEventListener('click', () => {
        // todo
        showMessage(400, 'Данныя выдалены паспяхова!', 'Роля', '#' + id)
        document.getElementById('editUserReport' + id).remove()
    })
}

const allReportTasks = document.getElementsByClassName("report-task");
for (let i = 0; i < allReportTasks.length; i++) {
    const id = allReportTasks[i].getAttribute('data-index')
    allReportTasks[i].addEventListener('click', () => {
        // todo
        showMessage(400, 'Данныя выдалены паспяхова!', 'Планы, задачы, наводкі', '#' + id)
        document.getElementById('editReportTask' + id).remove()
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

const allBlockMarkers = document.getElementsByClassName("edit-block-marker");
for (let i = 0; i < allBlockMarkers.length; i++) {
    const id = allBlockMarkers[i].getAttribute('data-index')
    const block = allBlockMarkers[i].getAttribute('data-block')
    allBlockMarkers[i].addEventListener('click', () => {
        // todo
        showMessage(400, 'Данныя выдалены паспяхова!', 'Эпізод', '#' + id)
        document.getElementById('editBlock' + block + 'Marker' + id).remove()
    })
}

const allBlockSubjects = document.getElementsByClassName("edit-block-subject");
for (let i = 0; i < allBlockSubjects.length; i++) {
    const id = allBlockSubjects[i].getAttribute('data-index')
    const block = allBlockSubjects[i].getAttribute('data-block')
    allBlockSubjects[i].addEventListener('click', () => {
        // todo
        showMessage(400, 'Данныя выдалены паспяхова!', 'Прадмет', '#' + id)
        document.getElementById('editBlock' + block + 'Subject' + id).remove()
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
const saveReportUser = document.getElementById('saveReportUser')
if (saveReportUser) {
    saveReportUser.addEventListener('click', () => {
        // todo
        const id = '00000'
        showMessage(200, 'Данныя захаваліся паспяхова!', 'Даследвальнік', '#' + id)
        const modalElement = document.getElementById('addReportUserModal')
        const modal = bootstrap.Modal.getInstance(modalElement)
        modal.hide()
    })
}

const saveReportTask = document.getElementById('saveReportTask')
if (saveReportTask) {
    saveReportTask.addEventListener('click', () => {
        // todo
        const id = '00000'
        showMessage(200, 'Данныя захаваліся паспяхова!', 'План, задача, наводка', '#' + id)
        const modalElement = document.getElementById('addTaskPlanModal')
        const modal = bootstrap.Modal.getInstance(modalElement)
        modal.hide()
    })
}

const saveNewOrganization = document.getElementById('saveNewOrganization')
if (saveNewOrganization) {
    saveNewOrganization.addEventListener('click', () => {
        // todo
        const id = '00000'
        showMessage(200, 'Данныя захаваліся паспяхова!', 'Арганізацыя', '#' + id)
        const modalElement = document.getElementById('addOrgModal')
        const modal = bootstrap.Modal.getInstance(modalElement)
        modal.hide()
    })
}

const saveNewInformant = document.getElementById('saveNewInformant')
if (saveNewInformant) {
    saveNewInformant.addEventListener('click', () => {
        // todo
        const id = '00000'
        showMessage(200, 'Данныя захаваліся паспяхова!', 'Інфармант', '#' + id)
        const modalElement = document.getElementById('addInformantModal')
        const modal = bootstrap.Modal.getInstance(modalElement)
        modal.hide()
    })
}

const saveNewEpisode = document.getElementById('saveNewEpisode')
if (saveNewEpisode) {
    saveNewEpisode.addEventListener('click', () => {
        // todo
        const id = '00000'
        showMessage(200, 'Данныя захаваліся паспяхова!', 'Эпізод блока', '#' + id)
        const modalElement = document.getElementById('addEpisodeModal')
        const modal = bootstrap.Modal.getInstance(modalElement)
        modal.hide()
    })
}

const saveNewSubject = document.getElementById('saveNewSubject')
if (saveNewSubject) {
    saveNewSubject.addEventListener('click', () => {
        // todo
        const id = '00000'
        showMessage(200, 'Данныя захаваліся паспяхова!', 'Прадмет', '#' + id)
        const modalElement = document.getElementById('addSubjectModal')
        const modal = bootstrap.Modal.getInstance(modalElement)
        modal.hide()
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
