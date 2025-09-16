// Экспорт csv-файла
let exportFile = function (selectSections, filterName, pathScript, btnExportFile) {
    let formData = new FormData();
    let request = new XMLHttpRequest();
    request.open('POST', pathScript, true);
    //request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.responseType = 'blob';

    request.onload = function() {
        if (request.status === 200) {
            //console.log(request.response);
            let disposition = request.getResponseHeader('content-disposition');
            let matches = disposition.split('=');
            let filename = (matches != null && matches[1]) ? matches[1] : 'prices.csv';

            let blob = new Blob([request.response], { type: 'application/csv' });
            let link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            btnExportFile.innerHTML = '<i class="lni lni-download"></i>Скачать excel';
            btnExportFile.removeAttribute('disabled');
            btnExportFile.classList.remove('px-5');
        } else {
            console.log(`Ошибка ${request.status}: ${request.statusText}`);
        }
    };

    let arSections = Array.from(selectSections.options).filter(option => option.selected).map(option => option.value);
    //console.log(arSections);
    formData.append('SECTIONS', JSON.stringify(arSections));
    formData.append('NAME', filterName);
    request.send(formData);
};

document.addEventListener('DOMContentLoaded', (event) => {
    let filterFormPrices = document.forms.filterFormPrices;

    let filterName = filterFormPrices.elements.NAME;
    let clearInput = filterFormPrices.querySelector('.input-group-append .input-group-text.clear-input');

    let selectSections = document.getElementById('selectSections');

    let btnExportFile = document.getElementById('btnExportFile');

    btnExportFile.addEventListener('click', (event) => {
        event.preventDefault();
        //let pathScript = event.target.dataset.path;
        btnExportFile.innerHTML = '<img class="btn-preload" src="/local/templates/b2b/img/btn-preload.svg">';
        btnExportFile.setAttribute('disabled', 'disabled');
        btnExportFile.classList.add('px-5');
        exportFile(selectSections, filterName.value, pathScriptJS, btnExportFile);
    });

    clearInput.addEventListener('click', () => {
        filterName.value = '';
        filterName.focus();
    });

});