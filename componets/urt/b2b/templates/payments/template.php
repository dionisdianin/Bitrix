<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>

<div class="page-content">
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Всего оплат: <?= $arResult['TOTAL_COUNT'] ?></div>
        <div class="ms-auto">
            <div class="btn-group">
                <button type="button" class="btn btn-light">Настройки</button>
                <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown"><span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg-end">
                    <a class="dropdown-item" id="btnExportFile" href="javascript:;" >Скачать excel</a>
                    <!--                    <div class="dropdown-divider"></div>-->
                    <!--                        <a class="dropdown-item" href="javascript:;">Separated link</a>-->
                </div>
            </div>
        </div>
    </div>
    <!--end breadcrumb-->
    <div class="filter-block">
        <form class="row g-3" name="filterForm" action="<?= $APPLICATION->GetCurPage(); ?>">
            <div class="col-lg-3 col-md-4">
                <label class="form-label">Сумма оплаты:</label>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="number" min="0" max="2147483647" step="500" data-range="min" data-name="UF_PAYMENTS_SUM"
                               id="filterDateFrom" name="UF_PAYMENTS_SUM_MIN" class="form-control"
                               value="<?= $arResult['REQUEST']['UF_PAYMENTS_SUM_MIN']; ?>" placeholder="от">
                    </div>
                    <div class="col-md-6">
                        <input type="number" min="0" max="2147483647" step="500" data-range="max" data-name="UF_PAYMENTS_SUM"
                               id="filterDateTo" name="UF_PAYMENTS_SUM_MAX" class="form-control"
                               value="<?= $arResult['REQUEST']['UF_PAYMENTS_SUM_MAX']; ?>" placeholder="до">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <label for="PAYMENTSNAME" class="form-label">Наименование платежа:</label>
                <input type="text" maxlength="80" class="form-control" data-name="UF_PAYMENTS_NAME"
                        name="UF_PAYMENTS_NAME" id="PAYMENTSNAME"
                        value="<?= $arResult['REQUEST']['UF_PAYMENTS_NAME']; ?>">
            </div>
            <div class="col-12 d-flex justify-content-between">
                <div class="mb-2 d-flex">
                    <button type="submit" class="btn btn-light px-5">Показать</button>
                    <a type="button" class="btn btn-dark btn-reset-filter px-3" href="<?= $APPLICATION->GetCurPage(); ?>" title="Сбросить фильтр">
                        <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.11 2.697L2.698 4.11 6.586 8l-3.89 3.89 1.415 1.413L8 9.414l3.89 3.89 1.413-1.415L9.414 8l3.89-3.89-1.415-1.413L8 6.586l-3.89-3.89z" fill="#fff"></path>
                        </svg>
                    </a>
                </div>
                <div>
                    <button type="button" id="btnNewDataTable" class="d-none btn btn-danger px-3 d-flex align-items-center btn-new-data-table"
                            onclick="window.location.reload();">
                        <div><i class="lni lni-reload"></i></div>
                        <div class="ml-3"><span class="text-uppercase">Новые данные</span><br><small>обновите страницу</small></div>
                    </button>
                </div>
            </div>
            <input hidden name="HL_ID" value="<?= $arParams['HLBLOCK']; ?>">
        </form>
    </div>
    <hr/>
    <? if ($arResult['TOTAL_COUNT'] > 0) : ?>
    <div class="card">
        <div class="card-body">
            <?
            $APPLICATION->IncludeComponent(
                "bitrix:main.pagenavigation", "b2b",
                ["NAV_OBJECT" => $arResult['NAV'], "SEF_MODE" => "N" ],
            );
            ?>
            <div class="table-responsive">
                <table id="B2BPayments" class="table table-striped table-bordered">
                    <thead>
                    <?php
                    foreach ($arResult['FIELD_LABELS'] as $label) {
                        ?>
                        <th><?= $label ?></th>
                        <?php
                    }
                    ?>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($arResult['FIELD_VALUES'] as $arValues) {
                        //var_dump( $arValues);
                        ?>
                        <tr data-id="<?= $arValues['ID'] ?>" >
                            <?php
                            foreach ($arValues as $k => $value) {
                                if ($k == 'ID') continue;
                                ?>
                                <td><?= $value ; ?></td>
                                <?php
                            }
                            ?>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <?php
                        foreach ($arResult['FIELD_LABELS'] as $label) {
                            ?>
                            <th><?= $label ?></th>
                            <?php
                        }
                        ?>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <?
            $APPLICATION->IncludeComponent(
                "bitrix:main.pagenavigation", "b2b",
                ["NAV_OBJECT" => $arResult['NAV'], "SEF_MODE" => "N" ],
            );
            ?>
        </div>
    </div>
    <? else: ?>
        <p class="lead">Нет данных...</p>
    <? endif; ?>
</div>
<script type="text/javascript">
    let arParams = <?= CUtil::PhpToJSObject($arParams) ?>;
    let pathScriptJS = <?= CUtil::PhpToJSObject("{$componentPath}/export_csv.php") ?>;
</script>