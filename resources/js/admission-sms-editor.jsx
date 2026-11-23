import { Editor } from '@tinymce/tinymce-react';
import { createRoot } from 'react-dom/client';

import 'tinymce/tinymce';
import 'tinymce/models/dom/model';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/skins/ui/oxide/skin';
import 'tinymce/skins/content/default/content';
import 'tinymce/skins/ui/oxide/content';
import 'tinymce/plugins/lists';

function smsText(editor) {
    const content = editor.getBody();
    if (!content.querySelector('li')) {
        return editor.getContent({ format: 'text' });
    }

    const body = editor.getDoc().createElement('div');
    body.dataset.mceBogus = 'all';
    Object.assign(body.style, { position: 'fixed', left: '-10000px', top: '0' });
    body.append(...Array.from(content.childNodes, (node) => node.cloneNode(true)));
    body.querySelectorAll('li').forEach((item) => {
        const list = item.parentElement;
        const position = Array.from(list.children).indexOf(item) + 1;
        item.prepend(list.tagName === 'OL' ? `${position}. ` : '• ');
    });
    content.append(body);
    const text = body.innerText.trim();
    body.remove();
    return text;
}

function mountSmsEditor(host) {
    const textarea = document.getElementById(host.dataset.inputId);
    const counter = document.getElementById(host.dataset.counterId);
    const error = document.getElementById(host.dataset.errorId);
    const label = textarea.labels[0];
    const maxLength = textarea.maxLength;
    const initialContent = document.createElement('span');
    initialContent.textContent = textarea.value;

    const formatted = document.createElement('input');
    formatted.type = 'hidden';
    formatted.name = host.dataset.formattedName;
    formatted.value = host.dataset.initialHtml;

    function updateCounter() {
        counter.textContent = `${textarea.value.length} / ${maxLength}`;
    }

    function syncText(editor) {
        textarea.value = smsText(editor);
        formatted.value = editor.getContent();
        error.textContent = '';
        updateCounter();
    }

    createRoot(host).render(
        <Editor
            licenseKey={host.dataset.licenseKey}
            readonly={textarea.readOnly}
            initialValue={formatted.value || initialContent.innerHTML.replaceAll('\n', '<br>')}
            init={{
                menubar: false,
                plugins: 'lists',
                toolbar: 'undo redo | bold italic underline fontsize | bullist numlist | removeformat',
                font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px',
                statusbar: false,
                height: 185,
                paste_as_text: true,
                newline_behavior: 'linebreak',
                content_style: 'body { font: 15px/1.5 system-ui, sans-serif; overflow-wrap: anywhere; }',
            }}
            onInit={(_, editor) => {
                textarea.hidden = true;
                textarea.required = false;
                updateCounter();

                const frame = editor.getContainer().querySelector('iframe');
                frame.title = label.textContent.trim();
                label.addEventListener('click', (event) => {
                    event.preventDefault();
                    editor.focus();
                });

                textarea.form.append(formatted);
                textarea.form.addEventListener('submit', (event) => {
                    if (editor.isDirty()) {
                        syncText(editor);
                    }
                    const message = !textarea.value.trim()
                        ? 'Saisissez le texte du SMS.'
                        : textarea.value.length > maxLength
                          ? `Le SMS est limité à ${maxLength} caractères.`
                          : '';
                    if (message) {
                        error.textContent = message;
                        if (!event.defaultPrevented) {
                            editor.focus();
                        }
                        event.preventDefault();
                    }
                });
            }}
            onEditorChange={(_, editor) => syncText(editor)}
        />,
    );
}

document.querySelectorAll('[data-sms-tinymce]').forEach(mountSmsEditor);
