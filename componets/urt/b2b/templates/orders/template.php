<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>

<!--Modal-->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Заказ №<span></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть окно"></button>
            </div>
            <div class="modal-body">
                <ul class="info list-group"></ul>
                <h6>Товары:</h6>
                <div class="scroll-table">
                    <table class="table table-striped table-bordered table-goods">
                        <thead>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
<!--                <div class="modal-footer">-->
<!--                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>-->
<!--                </div>-->
        </div>
    </div>
</div>
<!--End Modal-->

<div class="page-content">
    <!--breadcrumb-->
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Всего заказов: <?= $arResult['TOTAL_COUNT'] ?></div>
        <div class="ms-auto">
            <div class="btn-group">
                <button type="button" class="btn btn-light">Настройки</button>
                <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown"><span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg-end">
                    <a class="dropdown-item" id="btnExportFile" href="javascript:;">Скачать excel</a>
<!--                    <a class="dropdown-item" href="/b2b/orders/test_csv.php" target="_blank" download="true">Скачать excel</a>-->
                    <!--                    <div class="dropdown-divider"></div>-->
                    <!--                        <a class="dropdown-item" href="javascript:;">Separated link</a>-->
                </div>
            </div>
        </div>
    </div>
    <!--end breadcrumb-->
    <div class="filter-block">
            <form class="row g-3" name="filterForm" action="<?= $APPLICATION->GetCurPage(); ?>">
                <div class="col-md-3">
                    <label for="INNBUYER" class="form-label">ИНН покупателя:</label>
                    <select class="form-select" aria-label="" data-name="UF_ORDER_INN_BUYER"
                            name="UF_ORDER_INN_BUYER"
                            id="INNBUYER">
                        <option></option>
                        <? foreach ($arResult['SELECTOR_INN'] as $inn) : ?>
                            <option value="<?= $inn ?>" <?= ($arResult['REQUEST']['UF_ORDER_INN_BUYER'] == $inn) ? 'selected' : ''; ?>>
                                <?= $inn ?>
                            </option>
                        <? endforeach; ?>
                    </select>
                </div>
<!--                <div class="col-md-2">-->
<!--                    <label for="ORDERSTATUS" class="form-label">Статус:</label>-->
<!--                    <select class="form-select" aria-label="" data-name="UF_ORDER_STATUS"-->
<!--                            name="UF_ORDER_STATUS"-->
<!--                            id="ORDERSTATUS">-->
<!--                        <option></option>-->
<!--                        --><?// foreach ($arResult['ORDER_STATUS'] as $status) : ?>
<!--                            <option value="--><?//= $status ?><!--" --><?//= ($arResult['REQUEST']['UF_ORDER_STATUS'] == $status) ? 'selected' : ''; ?><!-- >-->
<!--                                --><?//= $status ?>
<!--                            </option>-->
<!--                        --><?// endforeach; ?>
<!--                    </select>-->
<!--                </div>-->

<!--                <div class="col-md-2">-->
<!--                    <label for="COUNTERPARTY" class="form-label">ИНН</label>-->
<!--                     <input type="text" class="form-control" data-name="UF_ORDER_COUNTERPARTY"-->
<!--                           name="UF_ORDER_COUNTERPARTY"-->
<!--                           id="COUNTERPARTY"> -->
<!--                    <input type="text" class="form-control" data-name="UF_ORDER_INN_BUYER"-->
<!--                           name="UF_ORDER_INN_BUYER"-->
<!--                           id="INNBUYER">-->
<!--                </div>-->
                <div class="col-xl-4 col-lg-4 col-md-5">
                    <label class="form-label">Дата отгрузки:</label>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="date" data-range="from" data-name="UF_ORDER_SHIPPING_DATE" onchange="dateRestriction()"
                                   id="filterDateFrom" name="UF_ORDER_SHIPPING_DATE_FROM" class="form-control"
                                   value="<?= $arResult['REQUEST']['UF_ORDER_SHIPPING_DATE_FROM']; ?>">
                        </div>
                        <div class="col-md-6">
                            <input type="date" data-range="to" data-name="UF_ORDER_SHIPPING_DATE" onchange="dateRestriction()"
                                   id="filterDateTo" name="UF_ORDER_SHIPPING_DATE_TO" class="form-control"
                                   value="<?= $arResult['REQUEST']['UF_ORDER_SHIPPING_DATE_TO']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-between filter-line-2">
                    <div class="mb-2 d-flex btn-block">
                        <button type="submit" class="btn btn-light px-5">Показать</button>
                        <a type="reset" class="btn btn-dark btn-reset-filter px-3" href="<?= $APPLICATION->GetCurPage(); ?>" title="Сбросить фильтр">
                            <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4.11 2.697L2.698 4.11 6.586 8l-3.89 3.89 1.415 1.413L8 9.414l3.89 3.89 1.413-1.415L9.414 8l3.89-3.89-1.415-1.413L8 6.586l-3.89-3.89z" fill="#fff"></path>
                            </svg>
                        </a>
                        <div class="col-lg-7 col-sm-12 status-btn-block">
                            <input type="submit" class="btn btn-prefilter px-3 <?= ($arResult['REQUEST']['UF_ORDER_STATUS'] == 'Новый') ? 'active' : ''; ?>" name="UF_ORDER_STATUS" value="Новый" />
                            <input type="submit" class="btn btn-prefilter px-3 <?= ($arResult['REQUEST']['UF_ORDER_STATUS'] == 'В работе') ? 'active' : ''; ?>" name="UF_ORDER_STATUS" value="В работе" />
                            <input type="submit" class="btn btn-prefilter px-3 <?= ($arResult['REQUEST']['UF_ORDER_STATUS'] == 'Отгружен') ? 'active' : ''; ?>" name="UF_ORDER_STATUS" value="Отгружен" />
                        </div>
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
                <input hidden name="ORDER_STATUS" value="<?= $arResult['REQUEST']['UF_ORDER_STATUS']; ?>">
            </form>
        </div>
    <hr/>
    <? if ($arResult['TOTAL_COUNT'] > 0) : ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="B2BOrder" class="table table-striped table-bordered" >
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
                        ?>
                        <tr data-id="<?= $arValues['ID'] ?>" data-bs-toggle="modal" data-bs-target="#detailModal">
                            <?php
                            foreach ($arValues as $k => $value) :
                                if (in_array($k, ['ID', 'FULL_TEXT_DELIVERY_ADDRESS'])) continue;
                                ?>
                                <td
                                    class="<?= ($k != 'UF_ORDER_SUM') ?: 'white-space-nowrap' ?>"
                                    <?= ($k != 'UF_ORDER_DELIVERY_ADDRESS') ?: "data-fulltext = '{$arValues['FULL_TEXT_DELIVERY_ADDRESS']}'"; ?>
                                ><?= $value ?></td>
                            <?php
                            endforeach;
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
                "bitrix:main.pagenavigation",
                "b2b",
                array(
                    "NAV_OBJECT" => $arResult['NAV'],
                    "SEF_MODE" => "N",
                ),
                true
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