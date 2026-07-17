<template>
  <form @submit.prevent="save" class="space-y-4">

    <div>
      <label class="block text-xs text-gray-500 mb-1">Question Text *</label>
      <textarea v-model="form.text" required rows="3"
        class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none resize-none" />
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Category</label>
        <select v-model="form.category" class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none">
          <option value="general_knowledge">General knowledge</option>
          <option value="fifa_world_cup">FIFA World Cup</option>
          <option value="visa">Visa</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Type</label>
        <select v-model="form.type" @change="onTypeChange"
          class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none">
          <option value="multiple_choice">Multiple Choice</option>
          <option value="true_false">True / False</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Duration (seconds)</label>
        <input v-model.number="form.duration_seconds" type="number" min="5" max="120" required
          class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none text-center" />
      </div>
    </div>

    <div>
      <label class="block text-xs text-gray-500 mb-1">Options</label>
      <div class="space-y-2">
        <div v-for="(opt, idx) in form.options" :key="idx" class="flex items-center gap-2">
          <span class="text-xs text-gray-400 w-5">{{ labels[idx] }}</span>
          <input v-model="form.options[idx]" type="text" required
            :disabled="form.type === 'true_false'"
            class="flex-1 border rounded-xl px-3 py-2 text-sm focus:outline-none disabled:bg-gray-50 disabled:text-gray-400" />
          <input type="radio" :value="form.options[idx]" v-model="form.correct_answer"
            :title="`Mark '${form.options[idx]}' as correct`"
            class="accent-visa w-4 h-4" />
        </div>
      </div>
      <p class="text-xs text-gray-400 mt-1">Select the radio button next to the correct answer.</p>
    </div>

    <div class="flex items-center gap-2">
      <input v-model="form.is_double_points" type="checkbox" id="double" class="accent-visa-gold w-4 h-4" />
      <label for="double" class="text-sm text-gray-700">Double Points question</label>
    </div>

    <div>
      <label class="block text-xs text-gray-500 mb-1">Order Index</label>
      <input v-model.number="form.order_index" type="number" min="0"
        class="w-full border rounded-xl px-3 py-2 text-sm focus:outline-none" />
    </div>

    <p v-if="errorMsg" class="text-red-500 text-xs">{{ errorMsg }}</p>

    <div class="flex gap-3 pt-2">
      <button type="button" @click="$emit('cancel')"
        class="flex-1 bg-gray-100 text-gray-600 py-2 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">
        Cancel
      </button>
      <button type="submit" :disabled="saving"
        class="flex-1 bg-visa text-white py-2 rounded-xl text-sm font-semibold hover:bg-visa/80 disabled:opacity-50 transition">
        {{ saving ? 'Saving…' : 'Save Question' }}
      </button>
    </div>

  </form>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  initial: { type: Object, default: null },
})
const emit = defineEmits(['saved', 'cancel'])

const labels = ['A', 'B', 'C', 'D']

const form = reactive({
  text:             '',
  category:         'general_knowledge',
  type:             'multiple_choice',
  options:          ['', '', '', ''],
  correct_answer:   '',
  duration_seconds: 30,
  is_double_points: false,
  order_index:      0,
})

const saving   = ref(false)
const errorMsg = ref('')

onMounted(() => {
  if (props.initial) {
    Object.assign(form, {
      text:             props.initial.text,
      category:         props.initial.category ?? 'general_knowledge',
      type:             props.initial.type,
      options:          [...props.initial.options],
      correct_answer:   props.initial.correct_answer,
      duration_seconds: props.initial.duration_seconds,
      is_double_points: props.initial.is_double_points,
      order_index:      props.initial.order_index,
    })
  }
})

function onTypeChange() {
  if (form.type === 'true_false') {
    form.options       = ['True', 'False']
    form.correct_answer = ''
  } else {
    form.options       = ['', '', '', '']
    form.correct_answer = ''
  }
}

async function save() {
  if (!form.correct_answer) { errorMsg.value = 'Select the correct answer.'; return }
  if (form.options.some(o => !o.trim())) { errorMsg.value = 'Fill in all options.'; return }

  saving.value   = true
  errorMsg.value = ''
  try {
    const filteredOptions = form.type === 'true_false'
      ? ['True', 'False']
      : form.options.filter(Boolean)

    const payload = { ...form, options: filteredOptions }

    if (props.initial?.id) {
      await axios.put(`/api/admin/questions/${props.initial.id}`, payload)
    } else {
      await axios.post('/api/admin/questions', payload)
    }
    emit('saved')
  } catch (e) {
    errorMsg.value = e.response?.data?.message ?? 'Save failed.'
  } finally {
    saving.value = false
  }
}
</script>
