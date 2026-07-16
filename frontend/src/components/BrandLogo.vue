<template><img class="brand-logo" :src="light ? '/brand/tabor-ads-logo-light.svg' : '/brand/tabor-ads-logo.svg'" alt="Tabor Ads"></template>
<script setup>defineProps({light:{type:Boolean,default:false}})</script>
