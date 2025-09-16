<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>

<div>
    <form name="testForm">
        <div class="input-container">
            <input type="text" name="NAME" minlength="5" maxlength="50" placeholder="NAME" minlength="5" required>
        </div>
        <div class="input-container">
            <input type="email" name="EMAIL" minlength="5" maxlength="50" placeholder="EMAIL" minlength="5" required>
        </div>
        <div class="textarea-container">
            <textarea name="MESSAGE" rows="5" cols="40" placeholder="MESSAGE"></textarea>
        </div>
        <div class="btn-container">
            <input type="submit" class="btn btn-primary" value="Отправить">
        </div>
    </form>
</div>

<script type="text/javascript">
    let arParams = <?= CUtil::PhpToJSObject($arParams) ?>;
</script>