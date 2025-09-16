//Применить фильтр
let applyFilter = function (filter) {
    BX.ajax.runComponentAction(
        'kornilov:fin.info',
        'applyFilter',
        {
            mode: 'class',
            data: {
                filterJSON: JSON.stringify(filter),
                params: arParamsFinInfo
            }
        }
    ).then(function (response) {
        //console.log(response.data);
        updateInfo(response.data);

    }).catch(function (error) {
        console.error('Ошибка:', error);
    });
};

// Обновить информацию в шаблоне
let updateInfo = function (data) {
    let sumKL = document.querySelector('.fin-info-block .sum-kl');
    let sumDZ = document.querySelector('.fin-info-block .sum-dz');
    let sumPDZ = document.querySelector('.fin-info-block .sum-pdz');

    sumKL.innerHTML = data.SUM.KL;
    sumDZ.innerHTML = data.SUM.DZ;
    sumPDZ.innerHTML = data.SUM.PDZ;
}

document.addEventListener('DOMContentLoaded', (event) => {

    let filterFormFinInfo = document.forms.filterFormFinInfo;
    let select = filterFormFinInfo.UF_B2B_INN;

    select.addEventListener('change', () => {
        let filter = {[select.name]: select.value};
        applyFilter(filter);
    });
});