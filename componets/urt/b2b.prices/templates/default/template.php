<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$this->addExternalJS($templateFolder . '/assets/jquery.min.js');
$this->addExternalJS($templateFolder . '/assets/bootstrap.bundle.min.js');
$this->addExternalJS($templateFolder . '/assets/bootstrap-select/bootstrap-select.js');
$this->addExternalCss($templateFolder . '/assets/bootstrap-select/bootstrap-select.css');
?>

<div class="page-content">
    <div>
        <form class="row g-3" name="filterFormPrices" action="<?= $APPLICATION->GetCurPage(); ?>">
            <div class="col-lg-3 col-md-6 d-flex">
                <div class="input-group">
                    <input type="text" maxlength="50" minlength="2" class="form-control" placeholder="Введите часть названия товара"
                           name="NAME" value="<?= $arResult['REQUEST_FILTER']['NAME']; ?>">
                    <div class="input-group-append">
                        <span class="input-group-text clear-input">
                            <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4.11 2.697L2.698 4.11 6.586 8l-3.89 3.89 1.415 1.413L8 9.414l3.89 3.89 1.413-1.415L9.414 8l3.89-3.89-1.415-1.413L8 6.586l-3.89-3.89z" fill="#fff"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 col-md-6">
                <select multiple class="selectpicker form-control"
                        name="SECTIONS[]" id="selectSections" title="Выберите разделы"
                        data-container="body"
                        data-live-search="true"
                        data-hide-disabled="true"
                        data-actions-box="true"
                        data-virtual-scroll="false">

                    <?
                    foreach ($arResult['ALL_SECTIONS'] as $arSection) : ?>
                        <option value="<?= $arSection['ID'] ?>"
                            <?= in_array(
                                $arSection['ID'],
                                $arResult['REQUEST_FILTER']['SECTIONS']
                            ) ? 'selected' : ''; ?>><?= $arSection['NAME'] ?></option>
                    <?
                    endforeach; ?>
                </select>
            </div>
            <div class="col-lg-4 col-md-12 d-flex">
                <button type="submit" class="btn btn-light px-3 btn-submit"><i class="lni lni-search"></i>Показать <span class="adaptive-text">цены</span></button>
                <a class="btn btn-dark px-3" id="resetFilter" href="<?= $APPLICATION->GetCurPage(); ?>" title="Сбросить фильтр">
                    <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M4.11 2.697L2.698 4.11 6.586 8l-3.89 3.89 1.415 1.413L8 9.414l3.89 3.89 1.413-1.415L9.414 8l3.89-3.89-1.415-1.413L8 6.586l-3.89-3.89z" fill="#fff"></path>
                    </svg>
                </a>
                <button class="btn btn-light px-3" id="btnExportFile">
                    <i class="lni lni-download"></i><span class="adaptive-text">Скачать </span>excel
                </button>
            </div>
        </form>
    </div>
    <hr/>
    <? if (count($arResult['ITEMS']) > 0) : ?>
    <div class="card">
        <div class="card-body">
            <?
            $APPLICATION->IncludeComponent(
                'bitrix:main.pagenavigation', 'b2b',
                ['NAV_OBJECT' => $arResult['NAV'], 'SEF_MODE' => 'N'],
            );
            ?>

            <div class="table-responsive">
                <table id="B2BPrices" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Название</th>
                        <th>Цена, с ндс</th>
                        <th>Остаток</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?
                    foreach ($arResult['ITEMS'] as $sectionId => $arSectionItems) : ?>
                    <thead style="background-color: rgba(0,38,71,0.47);">
                    <tr>
                        <th colspan="3" class="lead"><?= $arResult['ALL_SECTIONS'][$sectionId]['NAME'] ?></th>
                    </tr>
                    </thead>
                    <?
                    foreach ($arSectionItems as $arItem) : ?>
                        <tr>
                            <td class="name-product"><?= (mb_strlen($arItem['NAME']) > 88) ? mb_substr($arItem['NAME'], 0, 88) . '...' : $arItem['NAME']; ?></td>
                            <td><?= ((int)$arItem['PRICE_BASE'] > 0) ? number_format($arItem['PRICE_BASE'],2,'.',' ') . " ₽/{$arItem['MEASURE_SYMBOL_RUS']}" : 'по запросу'; ?></td>
                            <td><?= ((int)$arItem['QUANTITY'] > 0) ? "{$arItem['QUANTITY']} {$arItem['MEASURE_SYMBOL_RUS']}" : 'под заказ'; ?></td>
                        </tr>
                    <?
                    endforeach ?>
                    <?
                    endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>Название</th>
                        <th>Цена, с ндс</th>
                        <th>Остаток</th>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <?
            $APPLICATION->IncludeComponent(
                'bitrix:main.pagenavigation', 'b2b',
                ['NAV_OBJECT' => $arResult['NAV'], 'SEF_MODE' => 'N']
            );
            ?>
        </div>
    </div>
    <? else: ?>
        <p class="lead">Нет товаров по указанному фильтру...</p>
    <? endif; ?>
</div>

<script type="text/javascript">
    let pathScriptJS = <?= CUtil::PhpToJSObject("{$componentPath}/export_csv.php"); ?>
</script>