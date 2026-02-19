<script lang="ts" setup>
  defineProps<{
    title: string
    description: string
    variant?: 'default' | 'small'
  }>()
</script>

<template>
  <div :class="{ 'mb-6': variant !== 'small', 'mb-4': variant === 'small' }">
    <h2
      class="font-weight-bold text-grey-darken-3 mb-1"
      :class="variant === 'small' ? 'text-h6' : 'text-h5'"
    >
      {{ title }}
    </h2>
    <p class="text-body-2 text-grey-darken-1">
      {{ description }}
    </p>
  </div>
</template>
