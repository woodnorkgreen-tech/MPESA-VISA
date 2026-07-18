<template>
  <!-- Compact variant: used inline during trivia reveal, where vertical space is tight. -->
  <section v-if="compact" class="flex h-full w-full flex-col" aria-live="polite">
    <div class="mb-3 flex items-center justify-between gap-4 lg:mb-4">
      <h3 class="font-medium uppercase tracking-[.18em] text-white/72"
        style="font-size: clamp(0.65rem, 1.2vw, 1.2rem)">
        {{ title }}
      </h3>
      <p class="rounded-full border border-white/10 bg-black/20 px-3 py-1 font-medium text-white/62"
        style="font-size: clamp(.55rem,.8vw,.8rem)">
        {{ entries.length }} live
      </p>
    </div>

    <TransitionGroup name="leaderboard" tag="div" class="grid grid-cols-3 gap-2 lg:gap-4">
      <article v-for="entry in podium" :key="entryKey(entry)"
        class="relative min-w-0 overflow-hidden rounded-2xl border px-3 py-3 text-center lg:rounded-3xl lg:px-5 lg:py-5"
        :class="podiumClass(entry.rank)">
        <div class="mx-auto mb-1 flex h-8 w-8 items-center justify-center rounded-full bg-black/20 text-xl lg:h-11 lg:w-11 lg:text-3xl">
          {{ medal(entry.rank) }}
        </div>
        <p class="truncate font-medium text-white" style="font-size: clamp(.8rem,1.7vw,2rem)">
          {{ entry.nickname }}
        </p>
        <p class="mt-1 font-semibold tabular-nums text-visa-gold" style="font-size: clamp(1rem,2.2vw,2.7rem)">
          {{ score(entry).toLocaleString() }} <span class="text-[.45em] uppercase tracking-wider text-white/62">pts</span>
        </p>
      </article>
    </TransitionGroup>

    <TransitionGroup v-if="standings.length" name="leaderboard" tag="div"
      class="mt-2 grid min-h-0 flex-1 grid-cols-2 content-start gap-2 overflow-hidden lg:mt-3 lg:gap-3">
      <article v-for="entry in standings" :key="entryKey(entry)"
        class="flex min-w-0 items-center gap-3 rounded-xl border border-white/10 bg-white/[.055] px-3 py-2 lg:rounded-2xl lg:px-5 lg:py-3">
        <span class="w-7 shrink-0 text-center font-medium tabular-nums text-white/62"
          style="font-size: clamp(.8rem,1.3vw,1.4rem)">{{ entry.rank }}</span>
        <div class="min-w-0 flex-1">
          <p class="truncate font-medium text-white" style="font-size: clamp(.75rem,1.35vw,1.45rem)">
            {{ entry.nickname }}
          </p>
        </div>
        <span class="shrink-0 font-semibold tabular-nums text-visa-gold"
          style="font-size: clamp(.8rem,1.45vw,1.6rem)">{{ score(entry).toLocaleString() }}</span>
      </article>
    </TransitionGroup>

    <div v-if="!entries.length" class="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-white/10 text-white/58">
      Standings will appear here
    </div>
  </section>

  <!-- Full variant: dedicated leaderboard screens. -->
  <section v-else class="leaderboard-columns grid h-full w-full overflow-hidden rounded-3xl border border-white/10 bg-white/[.025]" aria-live="polite">
    <div class="flex min-h-0 flex-col items-center justify-center border-b border-white/10 px-5 py-5 text-center lg:border-b-0 lg:border-r lg:px-7 lg:py-6">
      <div v-if="winner" class="flex shrink-0 flex-col items-center">
        <div class="visa-winner-card" aria-label="Visa leaderboard card">
          <img src="/images/visa-logo.svg" alt="" class="visa-card-watermark" aria-hidden="true" />
          <span class="visa-card-sheen" aria-hidden="true"></span>
          <span class="visa-card-chip" aria-hidden="true"></span>
          <img src="/images/contactless-white.png" alt="" class="visa-card-contactless" aria-hidden="true" />
          <span class="visa-card-number" aria-hidden="true">4010&nbsp;1234&nbsp;5678&nbsp;9010</span>
          <span class="visa-card-valid" aria-hidden="true">GOOD THRU&nbsp;&nbsp;<strong>12/26</strong></span>
          <span class="visa-card-name" aria-hidden="true">A. N. OTHER</span>
          <img src="/images/visa-logo.svg" alt="" class="visa-card-logo" />
        </div>
        <p class="font-medium uppercase tracking-[.3em] text-white/72" style="font-size: clamp(.6rem,.9vw,.9rem)">
          Current leader
        </p>
        <p class="mt-2 max-w-full truncate font-medium text-white" style="font-size: clamp(1.35rem,2.4vw,2.8rem)">
          {{ winner.nickname }}
        </p>
        <p class="mt-1 font-semibold leading-none tabular-nums text-visa-gold" style="font-size: clamp(1.8rem,3.2vw,3.8rem)">
          {{ score(winner).toLocaleString() }}
        </p>
        <p class="mt-1 font-medium uppercase tracking-widest text-white/72" style="font-size: clamp(.55rem,.8vw,.85rem)">
          points
        </p>
      </div>

      <div v-else class="flex shrink-0 items-center justify-center text-center text-white/58">
        Standings will appear here
      </div>

    </div>

    <div class="flex min-h-0 flex-col px-4 py-4 lg:px-6 lg:py-5">
      <div class="mb-2 flex flex-shrink-0 items-center justify-between gap-4 border-b border-white/10 pb-3 lg:mb-3">
        <span class="flex items-center gap-2 font-medium uppercase tracking-widest text-white/76"
          style="font-size: clamp(.6rem,.85vw,.85rem)">
          <span class="h-2 w-2 rounded-full bg-visa-gold"></span> Live standings
        </span>
        <span class="font-medium text-white/68" style="font-size: clamp(.55rem,.8vw,.8rem)">
          {{ entries.length }} ranked players
        </span>
      </div>

      <TransitionGroup v-if="entries.length" name="leaderboard" tag="div"
        class="leaderboard-scroll min-h-0 flex-1 overflow-y-auto pr-1"
        style="min-height: calc(12 * clamp(2.15rem, 3.45vh, 3rem))">
        <article v-for="entry in entries" :key="entryKey(entry)"
          class="flex min-w-0 items-center gap-3 border-b border-white/5 px-1 last:border-b-0"
          style="min-height: clamp(2.15rem, 3.45vh, 3rem)">
          <span class="w-8 shrink-0 text-center font-medium tabular-nums" :class="rankColor(entry.rank)"
            style="font-size: clamp(.75rem,1.05vw,1.1rem)">
            {{ entry.rank }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-white" style="font-size: clamp(.72rem,1.05vw,1.15rem)">
              {{ entry.nickname }}
            </p>
          </div>
          <span class="shrink-0 font-semibold tabular-nums text-visa-gold"
            style="font-size: clamp(.75rem,1.15vw,1.25rem)">{{ score(entry).toLocaleString() }}</span>
        </article>
      </TransitionGroup>

      <div v-else class="flex flex-1 items-center justify-center text-white/58">
        Standings will appear here
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  entries: { type: Array, default: () => [] },
  title: { type: String, default: 'Leaderboard' },
  compact: { type: Boolean, default: false },
})

const podium = computed(() => props.entries.slice(0, 3))
const standings = computed(() => props.entries.slice(3, 10))
// No entry has been scored yet (e.g. predictions before the match result is
// submitted) â€” don't present a leader out of players who all sit at 0.
const winner = computed(() => {
  const top = props.entries[0]
  return top && score(top) > 0 ? top : null
})

function entryKey(entry) { return entry.id ?? `${entry.nickname}-${entry.rank}` }
function score(entry) { return Number(entry.trivia_score ?? entry.prediction_score ?? 0) }
function medal(rank) { return ['1', '2', '3'][rank - 1] ?? rank }
function podiumClass(rank) {
  if (rank === 1) return 'border-visa-gold/50 bg-gradient-to-b from-visa-gold/20 to-white/5 shadow-[0_0_35px_rgba(247,182,0,.12)]'
  if (rank === 2) return 'border-white/20 bg-gradient-to-b from-white/15 to-white/5'
  return 'border-amber-700/30 bg-gradient-to-b from-amber-700/15 to-white/5'
}
function rankColor(rank) {
  if (rank === 1) return 'text-visa-gold'
  if (rank === 2) return 'text-white/82'
  if (rank === 3) return 'text-visa-gold/85'
  return 'text-white/62'
}
</script>

<style scoped>
.leaderboard-move { transition: transform .45s cubic-bezier(.2,.8,.2,1); }
.leaderboard-enter-active, .leaderboard-leave-active { transition: all .3s ease; }
.leaderboard-enter-from { opacity: 0; transform: translateY(12px) scale(.98); }
.leaderboard-leave-to { opacity: 0; transform: translateY(-8px); }
.leaderboard-columns { grid-template-columns: minmax(13rem, 30%) 1fr; }
.visa-winner-card {
  position: relative;
  width: clamp(13.75rem, 23vw, 24rem);
  max-width: min(88vw, 100%);
  aspect-ratio: 1.586 / 1;
  margin-bottom: clamp(.75rem, 1.5vh, 1.25rem);
  overflow: hidden;
  border: 1px solid rgba(255,255,255,.18);
  border-radius: clamp(1rem, 1.55vw, 1.45rem);
  background:
    radial-gradient(circle at 78% 18%, rgba(255,255,255,.13), transparent 30%),
    linear-gradient(170deg, rgba(255,255,255,.08), transparent 34%),
    linear-gradient(156deg, #173ed1 0%, #1233b9 48%, #0a277f 100%);
  box-shadow: 0 26px 58px rgba(0,0,0,.38), inset 0 1px 0 rgba(255,255,255,.18);
  transform: perspective(700px) rotateX(2deg);
}
.visa-card-watermark {
  position: absolute;
  right: -18%;
  top: 0;
  width: 104%;
  height: auto;
  opacity: .14;
  pointer-events: none;
}
.visa-card-sheen {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(90deg, rgba(0,0,0,.08), transparent 28%, rgba(255,255,255,.045) 56%, transparent 78%),
    radial-gradient(circle at 42% 72%, rgba(255,255,255,.045), transparent 36%);
  opacity: .8;
}
.visa-card-chip {
  position: absolute;
  left: 10.8%;
  top: 36%;
  width: 16.4%;
  aspect-ratio: 1.28;
  border: 1px solid rgba(35,43,74,.55);
  border-radius: .34rem;
  background:
    linear-gradient(90deg, transparent 31%, rgba(35,43,74,.55) 31% 33%, transparent 33% 67%, rgba(35,43,74,.55) 67% 69%, transparent 69%),
    linear-gradient(0deg, transparent 31%, rgba(35,43,74,.55) 31% 33%, transparent 33% 67%, rgba(35,43,74,.55) 67% 69%, transparent 69%),
    linear-gradient(135deg, #f6f7fb, #cfd3de 52%, #fefefe);
  box-shadow: inset 0 0 0 1px rgba(255,255,255,.55), 0 5px 12px rgba(0,0,0,.18);
}
.visa-card-contactless {
  position: absolute;
  left: 16%;
  top: 24.7%;
  width: 6.6%;
  height: auto;
  opacity: .96;
  transform: rotate(-5deg);
  filter: drop-shadow(0 2px 5px rgba(0,0,0,.16));
}
.visa-card-number {
  position: absolute;
  left: 10.4%;
  top: 61%;
  color: #fff;
  font-size: clamp(.86rem, 1.34vw, 1.46rem);
  font-weight: 330;
  letter-spacing: .105em;
  white-space: nowrap;
  text-shadow: 0 2px 12px rgba(0,0,0,.2);
}
.visa-card-valid {
  position: absolute;
  left: 10.5%;
  top: 73.5%;
  color: rgba(255,255,255,.94);
  font-size: clamp(.42rem, .55vw, .58rem);
  font-weight: 700;
  line-height: 1;
  letter-spacing: .03em;
}
.visa-card-valid strong {
  font-size: 1.42em;
  font-weight: 400;
  letter-spacing: .08em;
}
.visa-card-name {
  position: absolute;
  left: 10.5%;
  bottom: 10.2%;
  color: #fff;
  font-size: clamp(.62rem, .92vw, 1rem);
  font-weight: 420;
  letter-spacing: .11em;
}
.visa-card-logo {
  position: absolute;
  right: 8.8%;
  bottom: 8.8%;
  width: 20.5%;
  height: auto;
  filter: drop-shadow(0 2px 8px rgba(0,0,0,.18));
}
.leaderboard-scroll { scrollbar-width: thin; scrollbar-color: rgba(247,182,0,.5) rgba(255,255,255,.06); }
.leaderboard-scroll::-webkit-scrollbar { width: 7px; }
.leaderboard-scroll::-webkit-scrollbar-track { background: rgba(255,255,255,.05); border-radius: 999px; }
.leaderboard-scroll::-webkit-scrollbar-thumb { background: rgba(247,182,0,.5); border-radius: 999px; }
@media (max-width: 900px), (orientation: portrait) {
  .leaderboard-columns { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  .leaderboard-move, .leaderboard-enter-active, .leaderboard-leave-active { transition: none; }
}
</style>
