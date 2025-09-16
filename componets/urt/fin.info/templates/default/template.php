<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>

<div class="fin-info-block m-3 mt-1">
    <div class="menu-label mb-1">Фин. информация</div>

    <div class="form-fin-info-block mb-2">
        <form name="filterFormFinInfo" id="filterFormFinInfo" action="" style="<?= (count($arResult['SELECTOR_INN']) <= 1) ? 'display: none;' : '' ?>">
            <div class="">
<!--                <label for="UF_B2B_INN" class="m-1 form-label">Выберите ИНН:</label>-->
                <select class="form-select" aria-label="" data-name="UF_B2B_INN"
                        name="UF_B2B_INN"
                        id="UF_B2B_INN">
                    <option value="*">Все ИНН</option>
                    <? foreach ($arResult['SELECTOR_INN'] as $option) : ?>
                        <option value="<?= $option ?>">
                            <?= $option?></option>
                    <? endforeach; ?>
                </select>
            </div>
        </form>
    </div>
    <div class="rounded-1 p-2 mb-1" style="background-color: #e87768;">
        <div class="d-flex justify-content-between">
            <div>Остаток КЛ</div>
            <div><span class="sum-kl"><?= $arResult['SUM']['KL'] ?></span> ₽</div>
        </div>
    </div>
    <div class="rounded-1 p-2 mb-1" style="background-color: #e33e51;">
        <div class="d-flex justify-content-between">
            <div>ДЗ</div>
            <div><span class="sum-dz"><?= $arResult['SUM']['DZ'] ?></span> ₽</div>
        </div>
    </div>
    <div class="rounded-1 p-2 mb-1" style="background-color: #459f7b;">
        <div class="d-flex justify-content-between">
            <div>ПДЗ</div>
            <div><span class="sum-pdz"><?= $arResult['SUM']['PDZ'] ?></span> ₽</div>
        </div>
    </div>
</div>

<script>
    let arParamsFinInfo = <?= CUtil::PhpToJSObject($arParams) ?>
</script>
