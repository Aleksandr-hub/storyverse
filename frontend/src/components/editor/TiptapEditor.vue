<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import { watch, computed, onBeforeUnmount } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: string
  placeholder?: string
  disabled?: boolean
  minHeight?: string
}>(), {
  placeholder: 'Почніть писати тут...',
  disabled: false,
  minHeight: '500px'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'save': []
}>()

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({
      heading: {
        levels: [1, 2, 3]
      }
    }),
    Placeholder.configure({
      placeholder: props.placeholder
    })
  ],
  editorProps: {
    attributes: {
      class: 'prose-editor',
      style: `min-height: ${props.minHeight}`
    },
    handleKeyDown: (view, event) => {
      // Ctrl+S or Cmd+S to save
      if ((event.ctrlKey || event.metaKey) && event.key === 's') {
        event.preventDefault()
        emit('save')
        return true
      }
      return false
    }
  },
  onUpdate: ({ editor }) => {
    emit('update:modelValue', editor.getHTML())
  },
  editable: !props.disabled
})

// Watch for external content changes
watch(() => props.modelValue, (newContent) => {
  if (editor.value && editor.value.getHTML() !== newContent) {
    editor.value.commands.setContent(newContent, { emitUpdate: false })
  }
})

// Watch for disabled state changes
watch(() => props.disabled, (disabled) => {
  editor.value?.setEditable(!disabled)
})

// Word count (supports Ukrainian/Cyrillic)
const wordCount = computed(() => {
  if (!editor.value) return 0
  const text = editor.value.getText()
  if (!text) return 0
  const matches = text.match(/[\p{L}\p{N}']+/gu)
  return matches ? matches.length : 0
})

const characterCount = computed(() => {
  if (!editor.value) return 0
  return editor.value.getText().length
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})

// Expose for parent components
defineExpose({
  wordCount,
  characterCount,
  editor
})
</script>

<template>
  <div class="tiptap-editor" :class="{ disabled }">
    <!-- Toolbar -->
    <div v-if="editor" class="editor-toolbar">
      <div class="toolbar-group">
        <button
          type="button"
          @click="editor.chain().focus().toggleBold().run()"
          :class="{ active: editor.isActive('bold') }"
          :disabled="disabled"
          title="Жирний (Ctrl+B)"
        >
          <strong>B</strong>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleItalic().run()"
          :class="{ active: editor.isActive('italic') }"
          :disabled="disabled"
          title="Курсив (Ctrl+I)"
        >
          <em>I</em>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleStrike().run()"
          :class="{ active: editor.isActive('strike') }"
          :disabled="disabled"
          title="Закреслений"
        >
          <s>S</s>
        </button>
      </div>

      <div class="toolbar-divider"></div>

      <div class="toolbar-group">
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          :class="{ active: editor.isActive('heading', { level: 1 }) }"
          :disabled="disabled"
          title="Заголовок 1"
        >
          H1
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          :class="{ active: editor.isActive('heading', { level: 2 }) }"
          :disabled="disabled"
          title="Заголовок 2"
        >
          H2
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          :class="{ active: editor.isActive('heading', { level: 3 }) }"
          :disabled="disabled"
          title="Заголовок 3"
        >
          H3
        </button>
      </div>

      <div class="toolbar-divider"></div>

      <div class="toolbar-group">
        <button
          type="button"
          @click="editor.chain().focus().toggleBulletList().run()"
          :class="{ active: editor.isActive('bulletList') }"
          :disabled="disabled"
          title="Маркований список"
        >
          &bull;
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleOrderedList().run()"
          :class="{ active: editor.isActive('orderedList') }"
          :disabled="disabled"
          title="Нумерований список"
        >
          1.
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleBlockquote().run()"
          :class="{ active: editor.isActive('blockquote') }"
          :disabled="disabled"
          title="Цитата"
        >
          &ldquo;
        </button>
      </div>

      <div class="toolbar-divider"></div>

      <div class="toolbar-group">
        <button
          type="button"
          @click="editor.chain().focus().setHorizontalRule().run()"
          :disabled="disabled"
          title="Горизонтальна лінія"
        >
          &mdash;
        </button>
        <button
          type="button"
          @click="editor.chain().focus().undo().run()"
          :disabled="disabled || !editor.can().undo()"
          title="Скасувати (Ctrl+Z)"
        >
          &#8630;
        </button>
        <button
          type="button"
          @click="editor.chain().focus().redo().run()"
          :disabled="disabled || !editor.can().redo()"
          title="Повторити (Ctrl+Y)"
        >
          &#8631;
        </button>
      </div>

      <div class="toolbar-stats">
        <span class="stat">{{ wordCount.toLocaleString() }} слів</span>
        <span class="stat-divider">|</span>
        <span class="stat">{{ characterCount.toLocaleString() }} символів</span>
      </div>
    </div>

    <!-- Editor Content -->
    <EditorContent :editor="editor" class="editor-content" />
  </div>
</template>

<style scoped>
.tiptap-editor {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
}

.tiptap-editor.disabled {
  background: #f9fafb;
  opacity: 0.7;
}

/* Toolbar */
.editor-toolbar {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px 12px;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
  flex-wrap: wrap;
}

.toolbar-group {
  display: flex;
  gap: 2px;
}

.toolbar-divider {
  width: 1px;
  height: 24px;
  background: #d1d5db;
  margin: 0 8px;
}

.editor-toolbar button {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  font-size: 14px;
  color: #374151;
  background: transparent;
  transition: all 0.15s;
}

.editor-toolbar button:hover:not(:disabled) {
  background: #e5e7eb;
}

.editor-toolbar button.active {
  background: #4f46e5;
  color: #fff;
}

.editor-toolbar button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.toolbar-stats {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.75rem;
  color: #6b7280;
}

.stat-divider {
  color: #d1d5db;
}

/* Editor Content */
.editor-content {
  padding: 16px;
}

.editor-content :deep(.ProseMirror) {
  outline: none;
  line-height: 1.75;
  color: #111827;
  font-size: 1rem;
}

.editor-content :deep(.ProseMirror p) {
  margin: 0 0 1em;
}

.editor-content :deep(.ProseMirror p:last-child) {
  margin-bottom: 0;
}

.editor-content :deep(.ProseMirror h1) {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 1.5em 0 0.5em;
  line-height: 1.3;
}

.editor-content :deep(.ProseMirror h2) {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 1.25em 0 0.5em;
  line-height: 1.3;
}

.editor-content :deep(.ProseMirror h3) {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1em 0 0.5em;
  line-height: 1.4;
}

.editor-content :deep(.ProseMirror h1:first-child),
.editor-content :deep(.ProseMirror h2:first-child),
.editor-content :deep(.ProseMirror h3:first-child) {
  margin-top: 0;
}

.editor-content :deep(.ProseMirror strong) {
  font-weight: 600;
}

.editor-content :deep(.ProseMirror em) {
  font-style: italic;
}

.editor-content :deep(.ProseMirror s) {
  text-decoration: line-through;
}

.editor-content :deep(.ProseMirror ul),
.editor-content :deep(.ProseMirror ol) {
  padding-left: 1.5em;
  margin: 0 0 1em;
}

.editor-content :deep(.ProseMirror li) {
  margin-bottom: 0.25em;
}

.editor-content :deep(.ProseMirror blockquote) {
  border-left: 3px solid #d1d5db;
  padding-left: 1em;
  margin: 1em 0;
  color: #6b7280;
  font-style: italic;
}

.editor-content :deep(.ProseMirror hr) {
  border: none;
  border-top: 1px solid #e5e7eb;
  margin: 2em 0;
}

.editor-content :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  color: #9ca3af;
  pointer-events: none;
  float: left;
  height: 0;
}

/* Responsive */
@media (max-width: 640px) {
  .toolbar-stats {
    width: 100%;
    justify-content: center;
    margin-top: 8px;
    margin-left: 0;
    padding-top: 8px;
    border-top: 1px solid #e5e7eb;
  }

  .toolbar-divider:last-of-type {
    display: none;
  }
}
</style>
