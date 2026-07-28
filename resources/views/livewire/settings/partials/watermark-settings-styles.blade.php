@pushOnce('styles')
<style>
.agricart-product-watermark { max-width: 42rem; }
.agricart-product-watermark__alert { margin-bottom: 1rem; padding: 0.75rem 1rem; border: 1px solid var(--agricart-border, #EBEDF0); background: #fff; }
.agricart-product-watermark__alert.is-success { border-color: #83B735; background: #f4faea; }
.agricart-product-watermark__alert.is-danger { border-color: #dc2626; background: #fef2f2; }
.agricart-product-watermark__alert.is-warning { border-color: #FBBC34; background: #fffbeb; }
.agricart-product-watermark__panel { border: 1px solid var(--agricart-border, #EBEDF0); background: #fff; padding: 1.25rem; }
.agricart-product-watermark__head h2 { margin: 0 0 0.35rem; font-size: 1.125rem; }
.agricart-product-watermark__head p { margin: 0 0 0.5rem; color: #64748B; font-size: 0.875rem; }
.agricart-product-watermark__meta { font-size: 0.8125rem !important; }
.agricart-product-watermark__form { display: grid; gap: 1rem; margin-top: 1rem; }
.agricart-product-watermark__field, .agricart-product-watermark__logo, .agricart-product-watermark__check { display: grid; gap: 0.35rem; font-size: 0.875rem; }
.agricart-product-watermark__field input, .agricart-product-watermark__field select, .agricart-product-watermark__logo input[type=file] { border: 1px solid #EBEDF0; border-radius: 0; padding: 0.5rem 0.65rem; font: inherit; }
.agricart-product-watermark__help { margin: 0; color: #64748B; font-size: 0.8125rem; }
.agricart-product-watermark__logo-preview { width: 7.5rem; height: 7.5rem; object-fit: contain; border: 1px solid #EBEDF0; background: #F4F5F7; }
.agricart-product-watermark__marketplaces, .agricart-product-watermark__note { border: 1px solid #EBEDF0; padding: 0.85rem; display: grid; gap: 0.75rem; }
.agricart-product-watermark__marketplaces legend, .agricart-product-watermark__note-title { padding-inline: 0.25rem; font-weight: 700; margin: 0; font-size: 0.875rem; }
.agricart-product-watermark__actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.agricart-product-watermark__btn { border: 1px solid #EBEDF0; background: #fff; padding: 0.55rem 0.9rem; font: inherit; cursor: pointer; border-radius: 0; }
.agricart-product-watermark__btn.is-primary { background: #83B735; border-color: #83B735; color: #fff; font-weight: 700; }
.agricart-product-watermark__btn:disabled { opacity: 0.55; cursor: not-allowed; }
</style>
@endPushOnce
