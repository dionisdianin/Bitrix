// Экспорт csv-файла
let exportFile = function (form, pathScript) {
    let filterFormData = new FormData(form);
    let request = new XMLHttpRequest();
    request.open('POST', pathScript, true);
    //request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.responseType = 'blob';

    request.onload = function() {
        if (request.status === 200) {
            let disposition = request.getResponseHeader('content-disposition');
            let matches = disposition.split('=');
            let filename = (matches != null && matches[1] ? matches[1] : 'export.csv');

            let blob = new Blob([request.response], { type: 'application/csv' });
            let link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            console.log(request.response);
        }
    };
    request.send(filterFormData);
};


// Обновление информации на экране для юзера, по pull-событию
let updateDataForUser = function (inn) {
    BX.ajax.runComponentAction(
        'urt:b2b',
        'checkUserINN',
        {
            mode: 'class',
            data: {inn: inn, params: arParams}
        }
    ).then(function (response) {
        //console.log(response.data);
        if (response.data === true) {
            document.getElementById('btnNewDataTable').classList.remove('d-none');
        }
    }).catch(function (error) {
        console.error('Ошибка:', error);
    });
    return false;
}

document.addEventListener('DOMContentLoaded', (event) => {
    let filterForm = document.forms.filterForm;

    let btnExportFile = document.getElementById('btnExportFile');
    btnExportFile.addEventListener('click', (event) => {
        event.preventDefault();
        exportFile(filterForm, pathScriptJS);
    });

    // Перехватываем pull-событие на клиенте
    BX.addCustomEvent("onPullEvent", function(module_id, command, params) {
        //console.log(module_id, command, params);
        if (command == 'B2BPaymentsOnAfterUpdate' || command == 'B2BPaymentsOnAfterAdd' || command == 'B2BPaymentsOnAfterDelete') {
            updateDataForUser(params.FIELDS.UF_PAYMENTS_INN_PAYER);
        }
    });
    // Слушаем событие PULL_B2B_HL (автоматическое продление подписки, пока юзер на странице)
    BX.PULL.extendWatch('PULL_B2B_HL');
});