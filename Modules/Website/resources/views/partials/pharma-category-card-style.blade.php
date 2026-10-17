<style>
.pharma-category-card-custom{box-sizing:border-box;display:flex;flex-direction:column;justify-content:space-between;gap:14px;min-width:0;min-height:var(--category-card-height);padding:var(--category-card-padding);border:1px solid var(--category-card-border);border-radius:var(--category-card-radius);background:var(--category-card-bg);color:var(--category-card-text);font-size:var(--category-card-font);text-decoration:none;transition:transform .18s,box-shadow .18s,border-color .18s}
.pharma-category-card-custom:hover{transform:translateY(-2px);border-color:var(--category-card-accent);box-shadow:0 8px 22px rgba(15,23,42,.10)}
.pharma-category-card-custom:focus-visible{outline:2px solid var(--category-card-accent);outline-offset:3px}
.pharma-category-card-custom .pharma-category-card-top,.pharma-category-card-custom .pharma-category-card-bottom{display:flex;align-items:center;justify-content:space-between;gap:12px;min-width:0;width:100%}
.pharma-category-card-custom .pharma-category-card-top{min-height:var(--category-icon-size)}
.pharma-category-card-custom .pharma-category-card-icon{width:var(--category-icon-size);height:var(--category-icon-size);flex:0 0 var(--category-icon-size);display:flex;align-items:center;justify-content:center;border-radius:12px;background:#ecfdf5;color:var(--category-card-accent)}
.pharma-category-card-custom .pharma-category-card-icon svg{width:52%;height:52%}
.pharma-category-card-custom[data-icon-shadow="1"] .pharma-category-card-icon{box-shadow:0 2px 7px rgba(15,23,42,.11)}
.pharma-category-card-custom .pharma-category-card-content{display:flex;flex-direction:column;gap:5px;flex:1;min-width:0}
.pharma-category-card-custom .pharma-category-card-name{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden;line-height:1.45;font-size:inherit;font-weight:650;word-break:normal;overflow-wrap:normal;hyphens:none;color:inherit}
.pharma-category-card-custom .pharma-category-card-count{display:inline-flex;align-items:center;flex:0 0 auto;font-size:12px;font-weight:600;white-space:nowrap;line-height:1.35;color:var(--category-card-accent)}
.pharma-category-card-custom .pharma-category-card-count[data-count-style="badge"]{border-radius:999px;padding:5px 10px;background:#ecfdf5}
.pharma-category-card-custom .pharma-category-card-arrow{display:block;width:20px;height:20px;flex:0 0 20px;color:var(--category-card-accent)}
.pharma-category-card-custom[data-layout="horizontal"]{flex-direction:row;align-items:center;justify-content:flex-start}
.pharma-category-card-custom[data-layout="horizontal"] .pharma-category-card-top{width:auto;flex:0 0 auto}
.pharma-category-card-custom[data-layout="horizontal"] .pharma-category-card-bottom{flex:1;min-width:0}
.pharma-category-card-custom[data-layout="horizontal"] .pharma-category-card-top .pharma-category-card-count{display:none}
.pharma-category-card-custom[data-layout="horizontal"] .pharma-category-card-content{gap:6px}
.pharma-category-card-custom[data-preset="clean"]{background:#fff;color:#0f172a;border-color:#dbe5e7;box-shadow:0 2px 7px rgba(15,23,42,.04)}
.pharma-category-card-custom[data-preset="premium"]{background:var(--category-card-accent);color:#fff;border-color:var(--category-card-accent)}
.pharma-category-card-custom[data-preset="premium"] .pharma-category-card-icon{background:#fff;color:var(--category-card-accent)}
.pharma-category-card-custom[data-preset="premium"] .pharma-category-card-count,.pharma-category-card-custom[data-preset="premium"] .pharma-category-card-arrow{color:#fff}
.pharma-category-card-custom[data-preset="premium"] .pharma-category-card-count[data-count-style="badge"]{background:rgba(255,255,255,.18)}
.pharma-category-card-custom[data-preset="soft"]{background:#f0fdf8;color:#064e3b;border-color:#bbebd6}
.pharma-category-card-custom[data-preset="soft"] .pharma-category-card-icon{background:#fff}
@media(max-width:639px){.pharma-category-card-custom{padding:min(var(--category-card-padding),16px)}.pharma-category-grid-custom[data-mobile-columns="2"] .pharma-category-card-custom{min-height:150px}.pharma-category-grid-custom[data-mobile-columns="2"] .pharma-category-card-custom[data-layout="horizontal"]{flex-direction:column;align-items:stretch}}
@media(prefers-reduced-motion:reduce){.pharma-category-card-custom{transition:none}.pharma-category-card-custom:hover{transform:none}}
</style>