<template>
  <div>
    <label class="relative mb-3 block">
      <span class="sr-only">Search {{ label }}</span>
      <input v-model.trim="search" type="search" :placeholder="`Search ${label.toLowerCase()}…`"
        class="field-control px-4 py-3 pl-10 text-sm placeholder:text-white/30" />
      <span class="pointer-events-none absolute left-3.5 top-3 text-gray-500" aria-hidden="true">⌕</span>
    </label>
    <div class="max-h-[46dvh] space-y-3 overflow-y-auto overscroll-contain pr-1" role="radiogroup" :aria-label="label">
    <section v-for="group in filteredGroups" :key="group.name" class="rounded-xl border border-white/10 bg-black/10 p-2.5">
      <div class="mb-2 flex items-center justify-between gap-2 px-1">
        <h4 class="flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider text-white">
          <img v-if="group.flag" :src="group.flag" :alt="`${group.name} flag`"
            class="h-6 w-10 rounded object-cover shadow-sm ring-1 ring-white/20" />
          <span :class="group.flag ? 'sr-only' : ''">{{ group.name }}</span>
        </h4>
        <span class="text-[10px] font-semibold text-gray-500">{{ group.players.length }} players</span>
      </div>
      <div class="grid grid-cols-2 gap-1.5 sm:grid-cols-3">
        <button v-for="player in group.players" :key="`${group.name}-${player}`" type="button"
          role="radio" :aria-checked="modelValue === player" @click="$emit('update:modelValue', player)"
          class="flex min-h-11 items-center gap-2 rounded-lg border px-2 py-2 text-left transition active:scale-[.98]"
          :class="modelValue === player
            ? 'border-visa-gold bg-visa-gold/15 text-white shadow-[0_0_0_1px_rgba(247,182,0,.2)]'
            : 'border-white/10 bg-gray-800/80 text-gray-300 hover:border-white/25'">
          <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-black"
            :class="modelValue === player ? 'bg-visa-gold text-gray-950' : 'bg-white/10 text-gray-400'">
            {{ initials(player) }}
          </span>
          <span class="min-w-0 text-[11px] font-semibold leading-tight sm:text-xs">{{ player }}</span>
        </button>
      </div>
    </section>

    <p v-if="!filteredGroups.length" class="rounded-xl border border-dashed border-white/10 px-4 py-6 text-center text-sm text-gray-500">
      No player matches “{{ search }}”
    </p>

    <button type="button" role="radio" :aria-checked="modelValue === fallbackValue"
      @click="$emit('update:modelValue', fallbackValue)"
      class="w-full rounded-lg border px-3 py-2.5 text-left text-xs font-bold transition"
      :class="modelValue === fallbackValue ? 'border-visa-gold bg-visa-gold/15 text-white' : 'border-white/10 bg-gray-800/80 text-gray-400'">
      {{ fallbackLabel }}
    </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, required: true },
  groups: { type: Array, required: true },
  fallbackValue: { type: String, required: true },
  fallbackLabel: { type: String, required: true },
})

defineEmits(['update:modelValue'])
const search = ref('')
const filteredGroups = computed(() => {
  const query = search.value.toLowerCase()
  if (!query) return props.groups
  return props.groups
    .map(group => ({ ...group, players: group.players.filter(player => player.toLowerCase().includes(query)) }))
    .filter(group => group.players.length)
})

function initials(name) {
  return name.split(/\s+/).slice(0, 2).map(part => part[0]).join('').toUpperCase()
}
</script>
