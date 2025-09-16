let actionForm = function (form) {
    BX.ajax.runComponentAction(
        'wejet:test',
        'saveForm',
        {
            mode: 'class',
            data: {
                name: form.elements.NAME.value,
                email: form.elements.EMAIL.value,
                message: form.elements.MESSAGE.value,
                params: arParams,
                sessid: BX.message('bitrix_sessid')
            }
        }
    ).then(function (response) {
        if (response.data === true) {
            alert('Данные сохранены!');
        } else {
            alert('Ошибка!');
            console.log(response.data);
        }
    }).catch(function (error) {
        console.error('Ошибка: ', error);
    });
};

document.addEventListener('DOMContentLoaded', (event) => {
    let testForm = document.forms.testForm;
    testForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (testForm.checkValidity()) {
            actionForm(testForm);
        }
    });
});