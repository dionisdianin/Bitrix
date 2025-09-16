// let loadFilteredItems = function (page, filter) {
//     BX.ajax.runComponentAction(
//             'urt:b2b',
//             'getFilteredItems',
//             {
//                 mode: 'class',
//                 data: {
//                     params: {
//                         PAGE: page,
//                         FILTER: filter,
//                         arParams
//                     }
//                 }
//             }
//     ).then(function (response) {
//         //updateTable(response.data.items);
//         //updatePagination(response.data.nav);
//         let r = response.data;
//         renderTableBody(arParams['SELECTED_FIELDS'], r);
//
//     }).catch(function (error) {
//         console.error('Ошибка:', error);
//     });
// };

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

// Применить фильтр
// let applyFilter = function (filter) {
//     BX.ajax.runComponentAction(
//         'urt:b2b',
//         'applyFilter',
//         {
//             mode: 'class',
//             data: {
//                 filterJSON: JSON.stringify(filter),
//                 params: arParams
//             },
//             navigation: {
//                 page: 1
//             }
//         }
//     ).then(function (response) {
//         console.log(response.data);
//         renderTableBody(arParams['SELECTED_FIELDS'], response.data);
//
//     }).catch(function (error) {
//         console.error('Ошибка:', error);
//     });
// };

// Рендер тела таблицы, по результатам фильтра
let renderTableBody = function (fields, data) {
    let tbody = BX('B2BOrder').getElementsByTagName('tbody')[0];
    //console.log(data);
    tbody.innerHTML = '';

    data.forEach(order => {
        const row = document.createElement('tr');
        row.setAttribute('data-id', order.ID);
        row.setAttribute('data-bs-toggle', 'modal');
        row.setAttribute('data-bs-target', '#detailModal');

        Object.values(fields).forEach(field => {
            if (field != 'ID' && field != 'UF_ORDER_GOODS') {
                let cell = document.createElement('td');
                cell.textContent = order[field];
                row.appendChild(cell);
            }
        });
        tbody.appendChild(row);
        addEventTableRow();
    });
};

let dateRestriction = function () {
    let dateFrom = document.getElementById('filterDateFrom');
    let dateTo = document.getElementById('filterDateTo');

    dateTo.min = dateFrom.value;

    if (dateTo.value < dateFrom.value) {
        dateTo.value = dateFrom.value;
    }

    if (dateFrom.value > dateTo.value) {
        dateFrom.value = dateTo.value;
    }

};

// Добавляем контент в модальное окно
let addContentModal = function () {
    //console.log(this.dataset.id);
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
        detailModal.querySelector('.modal-title span').innerHTML = response.data.UF_ORDER_NUMBER;
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
    info.innerHTML += `<li class="list-group-item"><b>Статус:</b> ${data.UF_ORDER_STATUS}</li>`;
    info.innerHTML += `<li class="list-group-item"><b>Номер счета:</b> ${data.UF_ORDER_NUMBER_INVOICE}</li>`;
    info.innerHTML += `<li class="list-group-item"><b>ИНН продавца:</b> ${data.UF_ORDER_INN_SELLER}</li>`;
    info.innerHTML += `<li class="list-group-item"><b>ИНН покупателя:</b> ${data.UF_ORDER_INN_BUYER}</li>`;
    info.innerHTML += `<li class="list-group-item"><b>Склад отгрузки:</b> ${data.UF_ORDER_SHIPPING_WAREHOUSE}</li>`;
    info.innerHTML += `<li class="list-group-item"><b>Сумма:</b> ${data.UF_ORDER_SUM} ₽</li>`;
    if (data.UF_ORDER_PICKER) {
        info.innerHTML += `<li class="list-group-item"><b>Сборщик:</b> ${data.UF_ORDER_PICKER}</li>`;
    }
    info.innerHTML += `<li class="list-group-item"><b>Дата отгрузки:</b> ${data.UF_ORDER_SHIPPING_DATE}</li>`;
    if (data.UF_ORDER_DELIVERY_ADDRESS) {
        info.innerHTML += `<li class="list-group-item"><b>Адрес доставки:</b> ${data.UF_ORDER_DELIVERY_ADDRESS}</li>`;
    }
    if (data.UF_ORDER_TRACK_NUMBER) {
        info.innerHTML += `<li class="list-group-item"><b>Трек-номер:</b> ${data.UF_ORDER_TRACK_NUMBER}</li>`;
    }

    let tableGoods = modalBody.querySelector('.table-goods');
    let thead = tableGoods.querySelector('thead');
    let tbody = tableGoods.querySelector('tbody');
    thead.innerHTML = '';
    tbody.innerHTML = '';

    let goods = JSON.parse(data.UF_ORDER_GOODS);
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
    let tableB2BOrder = document.getElementById('B2BOrder');
    let rowCollect = tableB2BOrder.querySelectorAll('tbody tr');

    for (let row of rowCollect) {
        row.addEventListener('click', addContentModal);
    }
}

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
    // let filter = {};
    // filterForm.addEventListener('submit', (event) => {
    //     event.preventDefault();
    //     let elements = filterForm.elements;
    //     elements.forEach((e) => {
    //         let val = e.value;
    //         let name = e.dataset.name;
    //         if (e.type === 'submit' || !e.value.trim()) {
    //             return;
    //         }
    //         if (e.type === 'date') {
    //             if (e.dataset.range === 'from') {
    //                 filter[`>=${name}`] = `${val} 00:00:00`;
    //             } else if (e.dataset.range === 'to') {
    //                 filter[`<=${name}`] = `${val} 23:59:59`;
    //             }
    //         } else {
    //             filter[name] = val;
    //         }
    //     });
    //     //loadFilteredItems(1, filter);
    //     applyFilter(filter);
    // });

    let btnExportFile = document.getElementById('btnExportFile');
    btnExportFile.addEventListener('click', (event) => {
        event.preventDefault();
        exportFile(filterForm, pathScriptJS);
    });

    addEventTableRow();

    // Перехватываем pull-событие на клиенте
    BX.addCustomEvent("onPullEvent", function(module_id, command, params) {
        //console.log(module_id, command, params);
        if (command == 'B2BOrdersOnAfterUpdate' || command == 'B2BOrdersOnAfterAdd' || command == 'B2BOrdersOnAfterDelete') {
            // console.log(module_id, command, params);
            updateDataForUser(params.FIELDS.UF_ORDER_INN_BUYER);
         }
    });
    // Слушаем событие PULL_B2B_HL (автоматическое продление подписки, пока юзер на странице)
    BX.PULL.extendWatch('PULL_B2B_HL');
});