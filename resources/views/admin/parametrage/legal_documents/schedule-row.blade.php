<div class="schedule-row">
    <div class="form-grid">
        <label>Date<input type="date" name="schedule[{{ $index }}][date]" value="{{ $row['date'] ?? '' }}" required></label>
        <label>Heure<input type="time" name="schedule[{{ $index }}][time]" value="{{ $row['time'] ?? '' }}"></label>
        <label>Visite ou intervention<input name="schedule[{{ $index }}][title]" value="{{ $row['title'] ?? '' }}" maxlength="200" required></label>
        <label>Lieu / locaux concernés<input name="schedule[{{ $index }}][location]" value="{{ $row['location'] ?? '' }}" maxlength="150"></label>
        <label>Responsable<input name="schedule[{{ $index }}][responsible]" value="{{ $row['responsible'] ?? '' }}" maxlength="150"></label>
    </div>
    <button class="btn btn-outline-danger btn-sm remove-schedule" type="button">Retirer cette ligne</button>
</div>
