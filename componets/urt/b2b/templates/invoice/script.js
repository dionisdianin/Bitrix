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

// Добавляем контент в модальное окно
let addContentModal = function (event) {
    //event.stopPropagation();
    //console.log(event.type);
    let id = this.dataset.id;
    let detailModal = document.getElementById('detailModal');
    let modalBody = detailModal.querySelector('.modal-body');
    let content = '';

    BX.ajax.runComponentAction(
        'urt:b2b',
        'addContentModal',
        {
            mode: 'class',
            data: {id: id, params: arParams}
        }
    ).then(function (response) {
        //console.log(response.data);

        //modalBody.innerHTML = '';
        detailModal.querySelector('.modal-title span').innerHTML = response.data.UF_INVOICE_NUMBER;
        renderModalBody(modalBody, response.data);
        //modalBody.append(content);
        
    }).catch(function (error) {
        console.error('Ошибка:', error);
    });
}

// Рендерим контент для модального окна
let renderModalBody = function (modalBody, data) {

    let info = modalBody.querySelector('.info');
    info.innerHTML = '';
    info.innerHTML += `<li class="list-group-item"><b>Номер счета:</b> ${data.UF_INVOICE_NUMBER}</li>`;
    if (data.UF_INVOICE_ORDER_NUMBER) {
        info.innerHTML += `<li class="list-group-item"><b>Номер заказа:</b> ${data.UF_INVOICE_ORDER_NUMBER}</li>`;
    }
    info.innerHTML += `<li class="list-group-item"><b>Статус:</b> ${data.UF_INVOICE_STATUS}</li>`;
    info.innerHTML += `<li class="list-group-item"><b>ИНН продавца:</b> ${data.UF_INVOICE_INN_SELLER}</li>`;
    info.innerHTML += `<li class="list-group-item"><b>ИНН покупателя:</b> ${data.UF_INVOICE_INN_BUYER}</li>`;
    if (data.UF_INVOICE_PAYMENT) {
        info.innerHTML += `<li class="list-group-item"><b>Платежка:</b> ${data.UF_INVOICE_PAYMENT}</li>`;
    }
    if (data.UF_INVOICE_EMAIL) {
        info.innerHTML += `<li class="list-group-item"><b>Email для связи:</b> ${data.UF_INVOICE_EMAIL}</li>`;
    }
    info.innerHTML += `<li class="list-group-item"><b>Ответственный менеджер:</b> ${data.UF_INVOICE_MANAGER}</li>`;
    info.innerHTML += `<li class="list-group-item"><b>Ответственный оператор:</b> ${data.UF_INVOICE_OPERATOR}</li>`;
    info.innerHTML += `<li class="list-group-item"><b>Регион:</b> ${data.UF_INVOICE_REGION}</li>`;
    if (data.UF_INVOICE_SHIPPING_DAYS) {
        info.innerHTML += `<li class="list-group-item"><b>Срок отгрузки:</b> ${data.UF_INVOICE_SHIPPING_DAYS} дн.</li>`;
    }
    if (data.UF_INVOICE_SHIPPING_DATE) {
        info.innerHTML += `<li class="list-group-item"><b>Дата отгрузки:</b> ${data.UF_INVOICE_SHIPPING_DATE}</li>`;
    }

    let tableGoods = modalBody.querySelector('.table-goods');
    let thead = tableGoods.querySelector('thead');
    let tbody = tableGoods.querySelector('tbody');
    thead.innerHTML = '';
    tbody.innerHTML = '';

    let goods = JSON.parse(data.UF_INVOICE_GOODS);
    let arrNames =[]; // Массив с именами св-в товаров
    //console.log(goods);
    for (let item of goods) {
        let rowTbody = document.createElement('tr');
        //row.className = 'row';
        for (let key in item ) {
            if (!arrNames.includes(key)) arrNames.push(key);
            rowTbody.innerHTML += `<td>${item[key]}</td>`;
        }
        tbody.append(rowTbody);
    }

    let rowThead = document.createElement('tr');
    for (let name of arrNames) {
        rowThead.innerHTML += `<th scope="col">${name}</th>`;
    }
    thead.append(rowThead);
}

// Вешаем события на клик по каждой строке в таблице
let addEventTableRow = function () {
    let tableB2BInvoice = document.getElementById('B2BInvoice');
    let rowCollect = tableB2BInvoice.querySelectorAll('tbody tr');
    for (let row of rowCollect) {
        row.addEventListener('click', addContentModal);
    }
}

// Обновление информации на экране для юзера, по pull-событию
let updateDataForUser = function (id, inn) {
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
            if (localStorage.getItem('pullItemID') !== null) {
                let arID = JSON.parse(localStorage.getItem('pullItemID'));

                if (!arID.includes(id)) {
                    arID.push(id);
                    localStorage.setItem('pullItemID', JSON.stringify(arID));
                }
            } else {
                localStorage.setItem('pullItemID', JSON.stringify([id]));
            }
            //console.log(localStorage.getItem('pullItemID'));
            document.getElementById('btnNewDataTable').classList.remove('d-none');
        }
    }).catch(function (error) {
        console.error('Ошибка:', error);
    });
    return false;
}

// Скачать счет: костыль для сброса bs-события разворачивания модалки и последующих в очереди, подключать до bootstrap.bundle.min.js
document.addEventListener('click', (e) => {
            if (e.target.classList.contains('link-file-invoice')) {
                e.stopImmediatePropagation();
            }
        },
        true // Add listener to capturing phase
);

document.addEventListener('DOMContentLoaded', (event) => {

    let filterForm = document.forms.filterForm;

    let btnExportFile = document.getElementById('btnExportFile');
    btnExportFile.addEventListener('click', (event) => {
        event.preventDefault();
        exportFile(filterForm, pathScriptJS);
    });

    addEventTableRow();

    // Мигание строк при наличии новых данных по pull-событию
    if (localStorage.getItem('pullItemID') !== null) {
        let arPullItemID = JSON.parse(localStorage.getItem('pullItemID'));
        console.log(arPullItemID);
        arPullItemID.forEach((item, index) => {
            let tr = document.querySelector(`[data-id="${item}"]`);
            if (!!tr) {
                tr.classList.add('new-pull-data');
            }
        });
        localStorage.removeItem('pullItemID');
    }
    // Перехватываем pull-событие на клиенте
    BX.addCustomEvent("onPullEvent", function(module_id, command, params) {
        // console.log(BX.bitrix_sessid());
        // console.log(module_id, command, params);
        if (command == 'B2BInvoiceOnAfterUpdate' || command == 'B2BInvoiceOnAfterAdd' || command == 'B2BInvoiceOnAfterDelete') {
            // console.log(module_id, command, params);
            let id = params.ID;
            let inn = params.FIELDS.UF_INVOICE_INN_BUYER;
            updateDataForUser(id.ID, inn);
        }
    });
    // Слушаем событие PULL_B2B_HL (автоматическое продление подписки, пока юзер на странице)
    BX.PULL.extendWatch('PULL_B2B_HL');
});