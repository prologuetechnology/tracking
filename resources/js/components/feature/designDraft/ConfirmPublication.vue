<script setup>
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
defineProps({
  title: { type: String, required: true },
  description: { type: String, required: true },
  action: { type: String, required: true },
  busy: Boolean,
})
defineEmits([`confirm`])
</script>
<template>
  <AlertDialog
    ><AlertDialogTrigger as-child><slot /></AlertDialogTrigger>
    <AlertDialogContent
      ><AlertDialogHeader
        ><AlertDialogTitle>{{ title }}</AlertDialogTitle
        ><AlertDialogDescription>{{
          description
        }}</AlertDialogDescription></AlertDialogHeader
      >
      <AlertDialogFooter
        ><AlertDialogCancel>Cancel</AlertDialogCancel
        ><AlertDialogAction :disabled="busy" @click="$emit('confirm')">{{
          action
        }}</AlertDialogAction></AlertDialogFooter
      ></AlertDialogContent
    >
  </AlertDialog>
</template>
