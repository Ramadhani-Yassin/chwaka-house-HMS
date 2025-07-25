<div id="chwaka-cafe-maruu-banner" class="panel" style="position:relative;margin-bottom:20px;padding:0;box-shadow:0 2px 8px rgba(0,0,0,0.07);border:1px solid #e0e0e0;background:#fff;">
    <button type="button" id="close-cafe-maruu-banner" style="position:absolute;top:8px;right:12px;background:transparent;border:none;font-size:22px;line-height:1;color:#888;cursor:pointer;z-index:2;" aria-label="Close">&times;</button>
    <img src="/logo/cafe maruu.png" alt="Café Maruu" style="width:100%;max-width:100%;display:block;border-radius:4px 4px 0 0;">
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var closeBtn = document.getElementById('close-cafe-maruu-banner');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                var banner = document.getElementById('chwaka-cafe-maruu-banner');
                if (banner) banner.style.display = 'none';
            });
        }
    });
</script>
