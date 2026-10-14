<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h5 id="departureMonth" class="mb-0" aria-live="polite"></h5>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" id="departurePrevious" aria-label="Mois précédent">‹</button>
        <button type="button" class="btn btn-outline-primary" id="departureToday">Aujourd’hui</button>
        <button type="button" class="btn btn-outline-primary" id="departureNext" aria-label="Mois suivant">›</button>
    </div>
</div>
<p class="text-muted">Sorties prévues : en attente, approuvées, clôturées ou annulées. Sélectionnez une journée pour consulter ses sorties.</p>
<div class="departure-calendar-scroll"><div id="departureGrid" class="departure-grid" aria-label="Calendrier des sorties"></div></div>
<div id="departureAgenda" class="mt-3" aria-live="polite"></div>
<script type="application/json" id="departureEvents">{!! json_encode($calendarEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<style>
.departure-calendar-scroll{overflow-x:auto}
.departure-grid{display:grid;grid-template-columns:repeat(7,minmax(90px,1fr));min-width:630px;gap:5px}
.departure-day{min-height:100px;border:1px solid #e7ebf2;border-radius:8px;background:#fff;text-align:left;padding:8px;color:#18233b}
.departure-day:hover,.departure-day:focus{border-color:#315efb;background:#edf1ff}
.departure-day[aria-pressed="true"]{outline:2px solid #315efb}
.departure-day.outside{opacity:.5}.departure-day.today{border-color:#315efb}
.departure-day small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:5px}
.departure-heading{text-align:center;font-weight:600;padding:8px}
</style>