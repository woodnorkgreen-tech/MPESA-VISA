<template>
  <div class="w-full rounded-2xl border border-white/10 bg-black/25 px-4 py-3 lg:px-6">
    <div class="mb-2 flex items-center justify-between">
      <p class="text-[clamp(.6rem,1vw,1rem)] font-black uppercase tracking-[.18em] text-visa-gold">
        <span class="mr-1 inline-block h-2 w-2 animate-pulse rounded-full bg-visa-gold"></span> Live leaderboard
      </p>
      <p class="text-[clamp(.55rem,.8vw,.8rem)] text-gray-500">Updates automatically</p>
    </div>
    <TransitionGroup name="rank" tag="div" class="grid grid-cols-3 gap-2 lg:gap-4">
      <div v-for="entry in topEntries" :key="entry.id ?? entry.nickname"
        class="flex min-w-0 items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 transition-colors"
        :class="changedScores.has(entryKey(entry)) ? 'score-flash' : ''">
        <span class="shrink-0 text-[clamp(1rem,2vw,2rem)]">{{ medals[entry.rank - 1] ?? entry.rank }}</span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-[clamp(.7rem,1.3vw,1.35rem)] font-bold text-white">
            {{ entry.nickname }}
          </p>
          <p class="text-[clamp(.55rem,.75vw,.75rem)] font-bold" :class="movement(entry) > 0 ? 'text-visa-gold' : movement(entry) < 0 ? 'text-red-400' : 'text-gray-600'">
            {{ movement(entry) > 0 ? `▲ ${movement(entry)}` : movement(entry) < 0 ? `▼ ${Math.abs(movement(entry))}` : '—' }}
          </p>
        </div>
        <span class="shrink-0 text-[clamp(.75rem,1.4vw,1.5rem)] font-black tabular-nums text-visa-gold">{{ entry.trivia_score.toLocaleString() }}</span>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  entries: { type: Array, default: () => [] },
})
const previousRanks = ref(new Map())
const rankChanges = ref(new Map())
const previousScores = ref(new Map())
const changedScores = ref(new Set())
const topEntries = computed(() => props.entries.slice(0, 3))
const medals = ['🥇', '🥈', '🥉']
let flashTimer

function entryKey(entry) { return entry.id ?? entry.nickname }
function movement(entry) { return rankChanges.value.get(entryKey(entry)) ?? 0 }

watch(() => props.entries, entries => {
  const nextRanks = new Map(), nextScores = new Map(), changes = new Map(), scoreChanges = new Set()
  entries.forEach((entry, index) => {
    const key = entryKey(entry), rank = entry.rank ?? index + 1
    if (previousRanks.value.has(key)) changes.set(key, previousRanks.value.get(key) - rank)
    if (previousScores.value.has(key) && previousScores.value.get(key) !== entry.trivia_score) scoreChanges.add(key)
    nextRanks.set(key, rank); nextScores.set(key, entry.trivia_score)
  })
  rankChanges.value = changes; previousRanks.value = nextRanks; previousScores.value = nextScores
  changedScores.value = scoreChanges
  clearTimeout(flashTimer); flashTimer = setTimeout(() => { changedScores.value = new Set() }, 900)
}, { deep: true, immediate: true })
</script>

<style scoped>
.rank-move { transition: transform .45s ease; }
.score-flash { animation: score-flash .85s ease; }
@keyframes score-flash { 0%, 100% { background: rgba(255,255,255,.05); } 35% { background: rgba(0,198,90,.24); border-color: rgba(0,198,90,.55); } }
</style>
