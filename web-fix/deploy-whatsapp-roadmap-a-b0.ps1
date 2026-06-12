$Remote = "srv1534644"
$Root = "/www/wwwroot/admin-homes"
$Plugin = "$Root/app/Plugins/Whatsapp"
$Src = Join-Path $PSScriptRoot "plugins\Whatsapp\src"

$files = @(
    "Services\WhatsappInboxContextService.php",
    "Support\MetaApiErrorFormatter.php",
    "Support\WaInboxUiHelper.php",
    "Support\WaTemplateRenderHelper.php",
    "Models\WaMessage.php",
    "Http\Controllers\Admin\WhatsappDeliveryController.php",
    "Http\Controllers\Admin\WhatsappInboxController.php",
    "routes\web.php",
    "resources\views\admin\whatsapp\inbox.blade.php",
    "resources\views\admin\whatsapp\delivery.blade.php",
    "resources\views\admin\whatsapp\partials\nav.blade.php",
    "resources\views\admin\whatsapp\settings.blade.php",
    "resources\views\admin\whatsapp\templates.blade.php",
    "resources\views\admin\whatsapp\events.blade.php",
    "resources\lang\en\whatsapp.php",
    "resources\lang\hi\whatsapp.php"
)

foreach ($rel in $files) {
    $local = Join-Path $Src $rel
    $remote = "$Plugin/$($rel -replace '\\','/')"
    scp -o ConnectTimeout=20 $local "${Remote}:${remote}"
}

ssh -o ConnectTimeout=20 $Remote "cd $Root && php -l $Plugin/Http/Controllers/Admin/WhatsappDeliveryController.php && php artisan optimize:clear"
Write-Host "Deployed WhatsApp Phase A + B0 to $Remote"
