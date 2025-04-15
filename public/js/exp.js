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

function addActionUserRole(element) {
    const id = element.getAttribute('data-index')
    element.addEventListener('click', () => {
        // todo
        showMessage(400, 'Данныя выдалены паспяхова!', 'Роля', '#' + id)
        document.getElementById('editUserReport' + id).remove()
    })
}
const allReportUserRoles = document.getElementsByClassName("report-user-role");
for (let i = 0; i < allReportUserRoles.length; i++) {
    addActionUserRole(allReportUserRoles[i])
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
    saveReportUser.addEventListener('click', event => {
        const form = document.getElementById('formAddReportUser')
        if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
        } else {
            // todo: save user role
            let html = document.getElementById('createNewUserRoleTemplate').innerHTML
            html = html.replaceAll('USERFULLNAME', form.querySelector('select[name="user"]').value)
            const id = Math.round(Math.random() * 99999)
            html = html.replaceAll('USERROLEID', '' + id)
            html = html.replaceAll('USERROLENAME', 'roles' + (Math.random() * 10))
            let element = document.getElementById('allUserRoles')
            element.insertAdjacentHTML('afterbegin', html)
            let newUserRole = document.getElementById('editUserReport' + id).querySelector('button')
            addActionUserRole(newUserRole)

            showMessage(200, 'Данныя захаваліся паспяхова!', 'Даследвальнік', '#' + id)

            const modalElement = document.getElementById('addReportUserModal')
            bootstrap.Modal.getInstance(modalElement).hide()

            form.classList.remove('was-validated')
            form.reset()
        }
        form.classList.add('was-validated')
    })
}

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

const saveNewInformant = document.getElementById('saveNewInformant')
if (saveNewInformant) {
    saveNewInformant.addEventListener('click', event => {
        const form = document.getElementById('formAddInformant')
        if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
        } else {
            // todo
            const id = '00000'
            showMessage(200, 'Данныя захаваліся паспяхова!', 'Інфармант', '#' + id)

            const modalElement = document.getElementById('addInformantModal')
            bootstrap.Modal.getInstance(modalElement).hide()

            form.classList.remove('was-validated')
            form.reset()
        }
        form.classList.add('was-validated')
    })
}

const saveNewEpisode = document.getElementById('saveNewEpisode')
if (saveNewEpisode) {
    saveNewEpisode.addEventListener('click', event => {
        const form = document.getElementById('formAddEpisode')
        if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
        } else {
            // todo
            const id = '00000'
            showMessage(200, 'Данныя захаваліся паспяхова!', 'Эпізод блока', '#' + id)

            const modalElement = document.getElementById('addEpisodeModal')
            bootstrap.Modal.getInstance(modalElement).hide()

            form.classList.remove('was-validated')
            form.reset()
        }
        form.classList.add('was-validated')
    })
}

const saveNewSubject = document.getElementById('saveNewSubject')
if (saveNewSubject) {
    saveNewSubject.addEventListener('click', event => {
        const form = document.getElementById('formAddSubject')
        if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
        } else {
            // todo
            const id = '00000'
            showMessage(200, 'Данныя захаваліся паспяхова!', 'Прадмет', '#' + id)

            const modalElement = document.getElementById('addSubjectModal')
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
