<template>
  <!-- Compact variant: used inline during trivia reveal, where vertical space is tight. -->
  <section v-if="compact" class="flex h-full w-full flex-col" aria-live="polite">
    <div class="mb-3 flex items-center justify-between gap-4 lg:mb-4">
      <h3 class="font-black uppercase tracking-[.18em] text-gray-400"
        style="font-size: clamp(0.65rem, 1.2vw, 1.2rem)">
        {{ title }}
      </h3>
      <p class="rounded-full border border-white/10 bg-black/20 px-3 py-1 font-bold text-gray-500"
        style="font-size: clamp(.55rem,.8vw,.8rem)">
        Top {{ entries.length }} · Live
      </p>
    </div>

    <TransitionGroup name="leaderboard" tag="div" class="grid grid-cols-3 gap-2 lg:gap-4">
      <article v-for="entry in podium" :key="entryKey(entry)"
        class="relative min-w-0 overflow-hidden rounded-2xl border px-3 py-3 text-center lg:rounded-3xl lg:px-5 lg:py-5"
        :class="podiumClass(entry.rank)">
        <div class="mx-auto mb-1 flex h-8 w-8 items-center justify-center rounded-full bg-black/20 text-xl lg:h-11 lg:w-11 lg:text-3xl">
          {{ medal(entry.rank) }}
        </div>
        <p class="truncate font-black text-white" style="font-size: clamp(.8rem,1.7vw,2rem)">
          {{ entry.nickname }}
        </p>
        <p class="mt-1 font-black tabular-nums text-visa-gold" style="font-size: clamp(1rem,2.2vw,2.7rem)">
          {{ score(entry).toLocaleString() }} <span class="text-[.45em] uppercase tracking-wider text-white/40">pts</span>
        </p>
      </article>
    </TransitionGroup>

    <TransitionGroup v-if="standings.length" name="leaderboard" tag="div"
      class="mt-2 grid min-h-0 flex-1 grid-cols-2 content-start gap-2 overflow-hidden lg:mt-3 lg:gap-3">
      <article v-for="entry in standings" :key="entryKey(entry)"
        class="flex min-w-0 items-center gap-3 rounded-xl border border-white/10 bg-white/[.055] px-3 py-2 lg:rounded-2xl lg:px-5 lg:py-3">
        <span class="w-7 shrink-0 text-center font-black tabular-nums text-gray-500"
          style="font-size: clamp(.8rem,1.3vw,1.4rem)">{{ entry.rank }}</span>
        <div class="min-w-0 flex-1">
          <p class="truncate font-bold text-white" style="font-size: clamp(.75rem,1.35vw,1.45rem)">
            {{ entry.nickname }}
          </p>
        </div>
        <span class="shrink-0 font-black tabular-nums text-visa-gold"
          style="font-size: clamp(.8rem,1.45vw,1.6rem)">{{ score(entry).toLocaleString() }}</span>
      </article>
    </TransitionGroup>

    <div v-if="!entries.length" class="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-white/10 text-gray-600">
      Scores will appear here
    </div>
  </section>

  <!-- Full variant: dedicated leaderboard screens. A single panel — champion showcase
       (winner on top, brand mark anchored to the foot) on the left, full standings
       table on the right filling the height (min 10 rows, rest on scroll). -->
  <section v-else class="leaderboard-columns grid h-full w-full overflow-hidden rounded-3xl border border-white/10 bg-white/[.025]" aria-live="polite">
    <div class="flex min-h-0 flex-col items-center border-b border-white/10 px-6 py-6 text-center lg:border-b-0 lg:border-r lg:px-8 lg:py-8">
      <div v-if="winner" class="flex shrink-0 flex-col items-center">
        <p class="font-bold uppercase tracking-[.3em] text-gray-500" style="font-size: clamp(.6rem,.9vw,.9rem)">
          Champion
        </p>
        <div class="my-3 flex shrink-0 items-center justify-center rounded-full border border-visa-gold/30 bg-visa-gold/10 lg:my-4"
          style="width: clamp(3.5rem,6vw,5.5rem); height: clamp(3.5rem,6vw,5.5rem)">
          <span style="font-size: clamp(1.5rem,2.6vw,2.6rem)">🏆</span>
        </div>
        <p class="max-w-full truncate font-black text-white" style="font-size: clamp(1.4rem,2.6vw,3rem)">
          {{ winner.nickname }}
        </p>
        <p class="mt-2 font-black leading-none tabular-nums text-visa-gold" style="font-size: clamp(2rem,3.6vw,4.2rem)">
          {{ score(winner).toLocaleString() }}
        </p>
        <p class="mt-2 font-bold uppercase tracking-widest text-gray-500" style="font-size: clamp(.55rem,.8vw,.85rem)">
          points
        </p>
      </div>

      <div v-else class="flex shrink-0 items-center justify-center text-center text-gray-600">
        Scores will appear here
      </div>

      <div class="mt-6 min-h-0 w-full flex-1 lg:mt-8" style="max-width: clamp(7rem, 11vw, 11.5rem)">
        <img src="/images/brand/mpesa-visa-hero.png" alt="M-PESA GlobalPay Visa card"
          class="h-full w-full rounded-xl object-contain opacity-95 shadow-xl" />
      </div>
    </div>

    <div class="flex min-h-0 flex-col px-6 py-6 lg:px-8 lg:py-8">
      <div class="mb-2 flex flex-shrink-0 items-center justify-between gap-4 border-b border-white/10 pb-3 lg:mb-3">
        <span class="flex items-center gap-2 font-bold uppercase tracking-widest text-gray-500"
          style="font-size: clamp(.6rem,.85vw,.85rem)">
          <span class="h-2 w-2 rounded-full bg-safaricom-light"></span> Live standings
        </span>
        <span class="font-bold text-gray-600" style="font-size: clamp(.55rem,.8vw,.8rem)">
          Top {{ entries.length }}
        </span>
      </div>

      <TransitionGroup v-if="entries.length" name="leaderboard" tag="div"
        class="leaderboard-scroll min-h-0 flex-1 overflow-y-auto pr-1"
        style="min-height: calc(10 * clamp(2.6rem, 4.4vh, 3.6rem))">
        <article v-for="entry in entries" :key="entryKey(entry)"
          class="flex min-w-0 items-center gap-4 border-b border-white/5 last:border-b-0"
          style="min-height: clamp(2.6rem, 4.4vh, 3.6rem)">
          <span class="w-8 shrink-0 text-center font-black tabular-nums" :class="rankColor(entry.rank)"
            style="font-size: clamp(.85rem,1.3vw,1.35rem)">
            {{ entry.rank }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate font-bold text-white" style="font-size: clamp(.8rem,1.35vw,1.45rem)">
              {{ entry.nickname }}
            </p>
          </div>
          <span class="shrink-0 font-black tabular-nums text-visa-gold"
            style="font-size: clamp(.85rem,1.5vw,1.7rem)">{{ score(entry).toLocaleString() }}</span>
        </article>
      </TransitionGroup>

      <div v-else class="flex flex-1 items-center justify-center text-gray-600">
        Scores will appear here
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
// submitted) — don't crown a "champion" out of players who all sit at 0.
const winner = computed(() => {
  const top = props.entries[0]
  return top && score(top) > 0 ? top : null
})

function entryKey(entry) { return entry.id ?? `${entry.nickname}-${entry.rank}` }
function score(entry) { return Number(entry.trivia_score ?? entry.prediction_score ?? 0) }
function medal(rank) { return ['🥇', '🥈', '🥉'][rank - 1] ?? rank }
function podiumClass(rank) {
  if (rank === 1) return 'border-visa-gold/50 bg-gradient-to-b from-visa-gold/20 to-white/5 shadow-[0_0_35px_rgba(247,182,0,.12)]'
  if (rank === 2) return 'border-white/20 bg-gradient-to-b from-white/15 to-white/5'
  return 'border-amber-700/30 bg-gradient-to-b from-amber-700/15 to-white/5'
}
function rankColor(rank) {
  if (rank === 1) return 'text-visa-gold'
  if (rank === 2) return 'text-gray-300'
  if (rank === 3) return 'text-amber-600'
  return 'text-gray-500'
}
</script>

<style scoped>
.leaderboard-move { transition: transform .45s cubic-bezier(.2,.8,.2,1); }
.leaderboard-enter-active, .leaderboard-leave-active { transition: all .3s ease; }
.leaderboard-enter-from { opacity: 0; transform: translateY(12px) scale(.98); }
.leaderboard-leave-to { opacity: 0; transform: translateY(-8px); }
.leaderboard-columns { grid-template-columns: minmax(13rem, 30%) 1fr; }
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
