<template>
  <Teleport to="body">
  <div class="fixed inset-0 bg-navy-950/80 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
    <div class="card w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="card-header flex items-center justify-between">
        <h2 class="text-sm font-semibold text-navy-200">Planifier une intervention</h2>
        <button @click="$emit('close')" class="text-navy-500 hover:text-navy-300">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <div class="p-5 space-y-4">
        <!-- Équipement -->
        <div>
          <label class="label">Équipement *</label>
          <div v-if="deviceId" class="input bg-navy-800/50 text-navy-300 cursor-default select-none">
            {{ deviceName || 'Appareil #' + deviceId }}
          </div>
          <select v-else v-model="form.device_id" class="input">
            <option value="">Sélectionner un appareil</option>
            <option v-for="d in devices" :key="d.id" :value="d.id">
              {{ d.name }} ({{ d.serial_number }})
            </option>
          </select>
        </div>

        <!-- Description / Titre -->
        <div>
          <label class="label">Description *</label>
          <input v-model="form.description" class="input" placeholder="Maintenance préventive trimestrielle" />
        </div>

        <!-- Type + Priorité -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="label">Type</label>
            <select v-model="form.type" class="input">
              <option value="preventif">Préventive</option>
              <option value="correctif">Corrective</option>
              <option value="calibration">Calibration</option>
              <option value="inspection">Inspection</option>
              <option value="remplacement">Remplacement</option>
            </select>
          </div>
          <div>
            <label class="label">Date planifiée *</label>
            <input v-model="form.scheduled_date" type="date" class="input" />
          </div>
        </div>

        <!-- Technicien -->
        <div>
          <label class="label">Technicien assigné</label>
          <select v-model="form.performed_by" class="input">
            <option value="">Non assigné</option>
            <option v-for="t in techniciens" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
        </div>

        <!-- Coût -->
        <div>
          <label class="label">Coût estimé (XAF)</label>
          <input v-model="form.cost" type="number" min="0" class="input" placeholder="0" />
        </div>

        <!-- Notes -->
        <div>
          <label class="label">Notes</label>
          <textarea v-model="form.notes" class="input h-20 resize-none" placeholder="Informations complémentaires..."></textarea>
        </div>

        <div v-if="error" class="p-3 rounded-lg bg-med-red/10 border border-med-red/25 text-med-red text-sm">
          {{ error }}
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="$emit('close')" class="btn-secondary">Annuler</button>
          <button @click="submit" class="btn-primary" :disabled="loading">
            <span v-if="loading" class="w-4 h-4 border-2 border-navy-800 border-t-transparent rounded-full animate-spin"></span>
            <span v-else>Planifier</span>
          </button>
        </div>
      </div>
    </div>
  </div>
  </Teleport>
</template>

<script setup lang="ts">
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useApi } from '~/composables/useApi'

const props = defineProps<{ deviceId?: number; deviceName?: string }>()
const emit  = defineEmits(['close', 'saved'])
const api   = useApi()

const devices     = ref<any[]>([])
const techniciens = ref<any[]>([])
const loading     = ref(false)
const error       = ref('')

const form = reactive({
  device_id:      props.deviceId ?? ('' as any),
  description:    '',
  type:           'preventif',
  scheduled_date: '',
  performed_by:   '' as any,
  cost:           '' as any,
  notes:          '',
})

async function loadRefs() {
  try {
    const techs = await api.get<any[]>('/referentiels/techniciens')
    techniciens.value = Array.isArray(techs) ? techs : []

    if (!props.deviceId) {
      const devs = await api.get<any[]>('/devices')
      devices.value = Array.isArray(devs) ? devs : []
    }
  } catch (e) { console.error(e) }
}

async function submit() {
  if (!form.device_id)         { error.value = 'Appareil requis.'; return }
  if (!form.description.trim()) { error.value = 'Description requise.'; return }
  if (!form.scheduled_date)    { error.value = 'Date planifiée requise.'; return }

  loading.value = true
  error.value   = ''
  try {
    await api.post('/maintenance', {
      device_id:      Number(form.device_id),
      description:    form.description,
      type:           form.type,
      scheduled_date: form.scheduled_date,
      performed_by:   form.performed_by || null,
      cost:           form.cost ? Number(form.cost) : null,
      notes:          form.notes || null,
    })
    emit('saved')
    emit('close')
  } catch (e: any) {
    const errors = e?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value = e?.data?.message ?? 'Erreur lors de la planification'
    }
  } finally {
    loading.value = false
  }
}

onMounted(loadRefs)
</script>
