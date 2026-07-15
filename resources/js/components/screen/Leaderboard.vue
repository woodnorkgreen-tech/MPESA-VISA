<template>
  <section class="flex h-full w-full flex-col" aria-live="polite">
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

    <!-- The leaders receive the strongest visual hierarchy for viewing at distance. -->
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
        <p v-if="showPhone && entry.phone_last4" class="truncate font-semibold text-white/50"
          style="font-size: clamp(.55rem,.8vw,.85rem)">
          •••• {{ entry.phone_last4 }}
        </p>
        <p class="mt-1 font-black tabular-nums text-visa-gold" style="font-size: clamp(1rem,2.2vw,2.7rem)">
          {{ score(entry).toLocaleString() }} <span class="text-[.45em] uppercase tracking-wider text-white/40">pts</span>
        </p>
      </article>
    </TransitionGroup>

    <!-- Remaining positions use two columns so ten entries fit without tiny text. -->
    <TransitionGroup v-if="standings.length" name="leaderboard" tag="div"
      class="mt-2 grid min-h-0 flex-1 grid-cols-2 content-start gap-2 overflow-hidden lg:mt-3 lg:gap-3">
      <article v-for="entry in standings" :key="entryKey(entry)"
        class="flex min-w-0 items-center gap-3 rounded-xl border border-white/10 bg-white/[.055] px-3 py-2 lg:rounded-2xl lg:px-5 lg:py-3">
        <span class="w-7 shrink-0 text-center font-black tabular-nums text-gray-500"
          style="font-size: clamp(.8rem,1.3vw,1.4rem)">{{ entry.rank }}</span>
        <div class="min-w-0 flex-1">
          <p class="truncate font-bold text-white" style="font-size: clamp(.75rem,1.35vw,1.45rem)">
            {{ entry.nickname }}
            <span v-if="showPhone && entry.phone_last4" class="font-medium text-gray-500"> · •••• {{ entry.phone_last4 }}</span>
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
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  entries: { type: Array, default: () => [] },
  showPhone: { type: Boolean, default: false },
  title: { type: String, default: 'Leaderboard' },
})

const podium = computed(() => props.entries.slice(0, 3))
const standings = computed(() => props.entries.slice(3, 10))

function entryKey(entry) { return entry.id ?? `${entry.nickname}-${entry.rank}` }
function score(entry) { return Number(entry.trivia_score ?? entry.prediction_score ?? 0) }
function medal(rank) { return ['🥇', '🥈', '🥉'][rank - 1] ?? rank }
function podiumClass(rank) {
  if (rank === 1) return 'border-visa-gold/50 bg-gradient-to-b from-visa-gold/20 to-white/5 shadow-[0_0_35px_rgba(247,182,0,.12)]'
  if (rank === 2) return 'border-white/20 bg-gradient-to-b from-white/15 to-white/5'
  return 'border-amber-700/30 bg-gradient-to-b from-amber-700/15 to-white/5'
}
</script>

<style scoped>
.leaderboard-move { transition: transform .45s cubic-bezier(.2,.8,.2,1); }
.leaderboard-enter-active, .leaderboard-leave-active { transition: all .3s ease; }
.leaderboard-enter-from { opacity: 0; transform: translateY(12px) scale(.98); }
.leaderboard-leave-to { opacity: 0; transform: translateY(-8px); }
@media (prefers-reduced-motion: reduce) {
  .leaderboard-move, .leaderboard-enter-active, .leaderboard-leave-active { transition: none; }
}
</style>
