import { EditorContent, useEditor, useEditorState, type Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { Bold, Heading2, Italic, Link2, List, ListOrdered, Quote, Redo2, Underline, Undo2, type LucideIcon } from 'lucide-react';
import { useEffect } from 'react';
import { cn } from '@/lib/utils';

/** Extensions communes à l'édition et à l'affichage : le rendu n'accepte que ces balises (pas de script ni de style). */
const extensions = [
    StarterKit.configure({
        heading: { levels: [2, 3] },
        link: { openOnClick: false, autolink: true, defaultProtocol: 'https', HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' } },
    }),
];

const contenuClasses =
    'text-sm leading-relaxed text-gray-800 [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-3 [&_blockquote]:border-secondary [&_blockquote]:pl-3 [&_blockquote]:text-muted [&_h2]:mt-3 [&_h2]:text-base [&_h2]:font-medium [&_h3]:mt-2 [&_h3]:font-medium [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1.5 [&_ul]:list-disc [&_ul]:pl-5';

type Bouton = { label: string; icon: LucideIcon; actif?: (editor: Editor) => boolean; action: (editor: Editor) => void };

const BOUTONS: Bouton[][] = [
    [
        { label: 'Gras', icon: Bold, actif: (e) => e.isActive('bold'), action: (e) => e.chain().focus().toggleBold().run() },
        { label: 'Italique', icon: Italic, actif: (e) => e.isActive('italic'), action: (e) => e.chain().focus().toggleItalic().run() },
        { label: 'Souligné', icon: Underline, actif: (e) => e.isActive('underline'), action: (e) => e.chain().focus().toggleUnderline().run() },
    ],
    [
        { label: 'Titre', icon: Heading2, actif: (e) => e.isActive('heading', { level: 2 }), action: (e) => e.chain().focus().toggleHeading({ level: 2 }).run() },
        { label: 'Liste à puces', icon: List, actif: (e) => e.isActive('bulletList'), action: (e) => e.chain().focus().toggleBulletList().run() },
        { label: 'Liste numérotée', icon: ListOrdered, actif: (e) => e.isActive('orderedList'), action: (e) => e.chain().focus().toggleOrderedList().run() },
        { label: 'Citation', icon: Quote, actif: (e) => e.isActive('blockquote'), action: (e) => e.chain().focus().toggleBlockquote().run() },
        {
            label: 'Lien',
            icon: Link2,
            actif: (e) => e.isActive('link'),
            action: (e) => {
                const actuel = e.getAttributes('link').href as string | undefined;
                const url = window.prompt('Adresse du lien (laisser vide pour retirer le lien)', actuel ?? 'https://');
                if (url === null) return;
                if (url.trim() === '') e.chain().focus().extendMarkRange('link').unsetLink().run();
                else e.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
            },
        },
    ],
    [
        { label: 'Annuler', icon: Undo2, action: (e) => e.chain().focus().undo().run() },
        { label: 'Rétablir', icon: Redo2, action: (e) => e.chain().focus().redo().run() },
    ],
];

type RichTextEditorProps = {
    id?: string;
    value: string;
    onChange: (html: string) => void;
    onBlur?: () => void;
    invalid?: boolean;
    placeholder?: string;
};

/** Texte vide pour Tiptap : un paragraphe sans contenu. */
export const htmlVide = (html: string) => html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim() === '';

/** Éditeur de texte riche Tiptap (remplace Summernote) ; la valeur échangée reste du HTML. */
export function RichTextEditor({ id, value, onChange, onBlur, invalid, placeholder }: RichTextEditorProps) {
    const editor = useEditor({
        extensions,
        content: value,
        immediatelyRender: false,
        editorProps: {
            attributes: {
                ...(id ? { id } : {}),
                'aria-multiline': 'true',
                role: 'textbox',
                ...(placeholder ? { 'aria-placeholder': placeholder } : {}),
                class: cn('min-h-48 max-h-96 overflow-y-auto px-3 py-2.5 outline-none', contenuClasses),
            },
        },
        onUpdate: ({ editor: courant }) => onChange(htmlVide(courant.getHTML()) ? '' : courant.getHTML()),
        onBlur: () => onBlur?.(),
    });
    const etat = useEditorState({
        editor,
        selector: ({ editor: courant }) => (courant ? BOUTONS.flat().map((bouton) => bouton.actif?.(courant) ?? false) : []),
    });

    // Valeur remplacée de l'extérieur (réinitialisation, modification d'une autre annonce).
    useEffect(() => {
        if (editor && value !== editor.getHTML() && !(value === '' && htmlVide(editor.getHTML()))) {
            editor.commands.setContent(value, { emitUpdate: false });
        }
    }, [editor, value]);

    let index = 0;

    return (
        <div
            className={cn(
                'overflow-hidden rounded-brand border border-gray-300 bg-white transition-colors focus-within:border-primary focus-within:ring-2 focus-within:ring-soft',
                invalid && 'border-red-500 focus-within:border-red-500 focus-within:ring-red-100',
            )}
        >
            <div role="toolbar" aria-label="Mise en forme" className="flex flex-wrap items-center gap-0.5 border-b bg-surface px-1.5 py-1">
                {BOUTONS.map((groupe, numero) => (
                    <div key={numero} className={cn('flex items-center gap-0.5', numero > 0 && 'border-l pl-1')}>
                        {groupe.map((bouton) => {
                            const actif = etat?.[index++] ?? false;
                            const Icone = bouton.icon;
                            return (
                                <button
                                    key={bouton.label}
                                    type="button"
                                    title={bouton.label}
                                    aria-label={bouton.label}
                                    aria-pressed={bouton.actif ? actif : undefined}
                                    disabled={!editor}
                                    onClick={() => editor && bouton.action(editor)}
                                    className={cn(
                                        'inline-flex size-8 cursor-pointer items-center justify-center rounded-brand text-muted transition-colors hover:bg-white hover:text-primary focus-visible:ring-2 focus-visible:ring-secondary focus-visible:outline-none',
                                        actif && 'bg-soft text-primary',
                                    )}
                                >
                                    <Icone size={18} strokeWidth={1.25} aria-hidden />
                                </button>
                            );
                        })}
                    </div>
                ))}
            </div>
            <EditorContent editor={editor} />
        </div>
    );
}

/** Affiche un contenu HTML d'annonce en le repassant par le schéma Tiptap (balises inconnues et scripts ignorés). */
export function RichTextView({ html, className }: { html: string; className?: string }) {
    const editor = useEditor({ extensions, content: html, editable: false, immediatelyRender: false }, [html]);
    return <EditorContent editor={editor} className={cn(contenuClasses, className)} />;
}
