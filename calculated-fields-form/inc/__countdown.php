<?php
$offer_text = 'End of Year Sales offer ';
$offer_time = '2026-01-01T23:59:59';
$currentTime = new DateTime();
$targetTime = new DateTime($offer_time);
if ($currentTime < $targetTime):
?>
<style>
	.countdown{text-align:center;width:100%;padding-top:10px;}
	.countdown-container {
		display: flex;
		gap: 0.4rem;
		justify-content: center;
		align-items: center;
		flex-wrap: wrap;
		padding: 0 1rem;
	}

	.countdown-title { display:block; text-align: center;}
	.countdown-time-unit {
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.countdown-time-box {
		width: 3rem;
		height: 3rem;
		background: white;
		border: 1px solid #cbd5e1;
		border-radius: 0.5rem;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 0;
	}

	.countdown-time-value {
		font-size: 1.5rem;
		font-weight: 700;
		color: #1e293b;
	}

	.countdown-time-label {
		font-size: 0.5rem;
		font-weight: 600;
		color: #64748b;
		text-transform: uppercase;
		letter-spacing: 0.1em;
	}

	.countdown-separator {
		font-size: 1.875rem;
		font-weight: 300;
		color: #cbd5e1;
		margin-bottom: 0.5rem;
	}

	@media (max-width: 640px) {
		.countdown-time-box {
			width: 50px;
			height: 50px;
		}

		.countdown-time-value {
			font-size: 1.875rem;
		}

		.countdown-container {
			gap: 5px;
		}
	}

	.cff-offer-a{
		font-size:1.3em;text-decoration:none;color:#2271b1;border:2px solid #2271b1; padding:2px 10px;border-radius:5px;box-shadow:none !important;outline:none !important;
	}

	.cff-offer-a:hover,
	.cff-offer-a:active,
	.cff-offer-a:focus{
		background:#2271b1;border-color:#2271b1;color:white;
	}
</style>
<div class="countdown">
	<h2 class="countdown-title" style="margin-top:10px;margin-bottom:15px;clear:both;"><?php print esc_html( $offer_text ); ?> <a href="https://cff.dwbooster.com/download" target="_blank" class="cff-offer-a"style="">40% OFF</a></h2>
	<div class="countdown-container">
		<div class="countdown-time-unit">
			<div class="countdown-time-box">
				<span class="countdown-time-value countdown_days">00</span>
			</div>
			<span class="countdown-time-label">Days</span>
		</div>

		<div class="countdown-separator">:</div>

		<div class="countdown-time-unit">
			<div class="countdown-time-box">
				<span class="countdown-time-value countdown_hours">00</span>
			</div>
			<span class="countdown-time-label">Hours</span>
		</div>

		<div class="countdown-separator">:</div>

		<div class="countdown-time-unit">
			<div class="countdown-time-box">
				<span class="countdown-time-value countdown_minutes">00</span>
			</div>
			<span class="countdown-time-label">Minutes</span>
		</div>

		<div class="countdown-separator">:</div>

		<div class="countdown-time-unit">
			<div class="countdown-time-box">
				<span class="countdown-time-value countdown_seconds">00</span>
			</div>
			<span class="countdown-time-label">Seconds</span>
		</div>
	</div>
</div>

<script>
	function closead_popup() {
		// document.querySelector('.ad-overlay').style.display = 'none';
	}
	if ( typeof updateCountdown == 'undefined' ) {
		function updateCountdown() {
			const targetDate = new Date('<?php print esc_js( $offer_time ); ?>').getTime();
			const now = new Date().getTime();
			const difference = targetDate - now;

			const countdownEls = document.getElementsByClassName('countdown');
			for( let i = 0, h = countdownEls.length; i < h; i++ ) {
				const countdownEl = countdownEls[i];
				if (difference > 0) {
					countdownEl.style.display="block";
					const days = Math.floor(difference / (1000 * 60 * 60 * 24));
					const hours = Math.floor((difference / (1000 * 60 * 60)) % 24);
					const minutes = Math.floor((difference / 1000 / 60) % 60);
					const seconds = Math.floor((difference / 1000) % 60);

					countdownEl.querySelector('.countdown_days').textContent = String(days).padStart(2, '0');
					countdownEl.querySelector('.countdown_hours').textContent = String(hours).padStart(2, '0');
					countdownEl.querySelector('.countdown_minutes').textContent = String(minutes).padStart(2, '0');
					countdownEl.querySelector('.countdown_seconds').textContent = String(seconds).padStart(2, '0');

				} else {
					countdownEl.style.display="none";
				}
			}
		}

		updateCountdown();
		setInterval(updateCountdown, 1000);
	}

</script>
<?php
endif;