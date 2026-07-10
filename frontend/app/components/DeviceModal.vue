<template>
  <Teleport to="body">
  <div class="fixed inset-0 bg-navy-950/85 backdrop-blur-sm z-[100] flex items-center justify-center p-4"
    @click.self="$emit('close')">
    <div class="card w-full max-w-2xl max-h-[92vh] overflow-y-auto animate-fade-up">
      <div class="card-header flex items-center justify-between sticky top-0 bg-navy-900 z-10">
        <h2 class="text-sm font-semibold text-navy-200">
          {{ device ? 'Modifier l\'équipement' : 'Nouvel équipement' }}
        </h2>
        <button @click="$emit('close')" class="text-navy-500 hover:text-navy-300 p-1 rounded">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <div class="p-5 space-y-4">
        <!-- Profils non chargés -->
        <div v-if="profilesError" class="p-3 rounded-lg bg-med-orange/10 border border-med-orange/30 text-med-orange text-xs">
          ⚠ Impossible de charger les types d'appareils. Vérifiez que le backend tourne.
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="label">Nom de l'équipement *</label>
            <input v-model="form.name" class="input" placeholder="Moniteur Cardiaque A1" autofocus />
          </div>

          <div>
            <label class="label">Numéro de série *</label>
            <input v-model="form.serial_number" class="input" placeholder="PM-CHIR-002"
              :disabled="!!device" :class="device ? 'opacity-50 cursor-not-allowed' : ''" />
            <p v-if="device" class="text-xs text-navy-600 mt-1">Non modifiable</p>
          </div>

          <div>
            <label class="label">Type d'appareil *</label>
            <select v-model="form.device_profile_id" class="input">
              <option value="">
                {{ profilesLoading ? 'Chargement...' : 'Sélectionner' }}
              </option>
              <option v-for="p in profiles" :key="p.id" :value="p.id">{{ p.label }}</option>
            </select>
          </div>

          <div class="col-span-2">
            <label class="label">Localisation *</label>
            <input v-model="form.location" class="input" placeholder="Bloc Opératoire, Salle 2" />
          </div>

          <div>
            <label class="label">Bâtiment</label>
            <input v-model="form.building" class="input" placeholder="Bâtiment A" />
          </div>

          <div>
            <label class="label">Étage</label>
            <input v-model="form.floor" class="input" placeholder="RDC" />
          </div>

          <div>
            <label class="label">Fabricant</label>
            <input v-model="form.manufacturer" class="input" placeholder="Philips" />
          </div>

          <div>
            <label class="label">Modèle</label>
            <input v-model="form.model" class="input" placeholder="IntelliVue MX450" />
          </div>

          <div>
            <label class="label">Date d'installation</label>
            <input v-model="form.installation_date" type="date" class="input" />
          </div>

          <div>
            <label class="label">Prochaine maintenance</label>
            <input v-model="form.next_maintenance_date" type="date" class="input" />
          </div>

          <div class="col-span-2">
            <label class="label">Notes</label>
            <textarea v-model="form.notes" class="input h-20 resize-none" placeholder="Informations complémentaires..."></textarea>
          </div>
        </div>

        <div v-if="error" class="p-3 rounded-lg bg-med-red/10 border border-med-red/25 text-med-red text-sm">
          {{ error }}
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="$emit('close')" class="btn-secondary">Annuler</button>
          <button @click="submit" class="btn-primary" :disabled="loading">
            <span v-if="loading" class="w-4 h-4 border-2 border-navy-800 border-t-transparent rounded-full animate-spin"></span>
            <span v-else>{{ device ? 'Mettre à jour' : 'Enregistrer' }}</span>
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

const props = defineProps<{ device?: any }>()
const emit  = defineEmits(['close', 'saved'])
const api   = useApi()

const profiles      = ref<any[]>([])
const profilesLoading = ref(true)
const profilesError = ref(false)
const loading       = ref(false)
const error         = ref('')

const form = reactive({
  name:                  '',
  serial_number:         '',
  device_profile_id:     '' as any,
  location:              '',
  building:              '',
  floor:                 '',
  manufacturer:          '',
  model:                 '',
  installation_date:     '',
  next_maintenance_date: '',
  notes:                 '',
})

if (props.device) {
  form.name                  = props.device.name ?? ''
  form.serial_number         = props.device.serial_number ?? ''
  form.device_profile_id     = props.device.profile?.id ?? props.device.device_profile_id ?? ''
  form.location              = props.device.location ?? ''
  form.building              = props.device.building ?? ''
  form.floor                 = props.device.floor ?? ''
  form.manufacturer          = props.device.manufacturer ?? ''
  form.model                 = props.device.model ?? ''
  form.installation_date     = props.device.installation_date ?? ''
  form.next_maintenance_date = props.device.next_maintenance_date ?? ''
  form.notes                 = props.device.notes ?? ''
}

async function loadProfiles() {
  profilesLoading.value = true
  profilesError.value   = false
  try {
    const data = await api.get<any[]>('/device-profiles')
    profiles.value = Array.isArray(data) ? data : []
    if (profiles.value.length === 0) profilesError.value = true
  } catch (e) {
    console.error('Erreur chargement profils:', e)
    profilesError.value = true
  } finally {
    profilesLoading.value = false
  }
}

async function submit() {
  error.value = ''
  if (!form.name.trim())          { error.value = 'Nom requis.'; return }
  if (!form.serial_number.trim()) { error.value = 'Numéro de série requis.'; return }
  if (!form.device_profile_id)    { error.value = 'Type d\'appareil requis.'; return }
  if (!form.location.trim())      { error.value = 'Localisation requise.'; return }

  loading.value = true
  try {
    if (props.device?.id) {
      const { serial_number, ...updateData } = form
      await api.put(`/devices/${props.device.id}`, {
        ...updateData,
        device_profile_id: Number(updateData.device_profile_id),
      })
    } else {
      await api.post('/devices', {
        ...form,
        device_profile_id: Number(form.device_profile_id),
      })
    }
    emit('saved')
    emit('close')
  } catch (e: any) {
    const errors = e?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value = e?.data?.message ?? 'Erreur lors de l\'enregistrement'
    }
  } finally {
    loading.value = false
  }
}

onMounted(loadProfiles)
</script>
