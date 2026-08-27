@extends('Admin::layouts.master')
@section('title', $type->name)
@section('content')
<div class="mx-auto w-full max-w-[1600px] p-3 sm:p-4 lg:p-6">
    @include('Request::partials.offline-runtime')
    @include('Request::partials.dashboard-back')
    <header class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Trình thiết kế loại đề nghị</p>
        <h1 class="mt-1 break-words text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $type->name }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-slate-600">{{ __('Request::request.designer_readiness') }}</p>
    </header>
    @livewire('request.admin.type-designer', ['typePublicId' => $type->public_id])
</div>

<script>
(() => {
    const openSections = new Set([0]);
    let draggedField = null;
    let selectedApprovalStage = 0;
    let pendingNewStageEmailOff = null;
    let observer = null;
    let scheduled = false;

    const designerRoot = () => document.querySelector('[aria-labelledby="request-designer-title"][wire\\:id]')
        ?? document.querySelector('[aria-labelledby="request-designer-title"]');

    const livewireComponent = (root) => {
        const host = root?.hasAttribute('wire:id') ? root : root?.closest('[wire\\:id]');
        const id = host?.getAttribute('wire:id');
        return id && window.Livewire ? window.Livewire.find(id) : null;
    };

    const alpineState = (root) => {
        try {
            return window.Alpine?.$data(root) ?? null;
        } catch (error) {
            return null;
        }
    };

    const sectionIndexFromArticle = (article) => {
        const key = article?.getAttribute('wire:key') ?? '';
        const match = key.match(/^section-structure-(\d+)$/);
        return match ? Number(match[1]) : null;
    };

    const fieldIndexesFromButton = (button) => {
        const key = button?.getAttribute('wire:key') ?? '';
        const match = key.match(/^field-picker-(\d+)-(\d+)$/);
        return match ? { section: Number(match[1]), field: Number(match[2]) } : null;
    };

    const setSectionOpen = (article, index, isOpen) => {
        const children = Array.from(article.children);
        children.slice(1).forEach((child) => {
            child.hidden = !isOpen;
        });

        const toggle = article.querySelector('[data-request-section-toggle]');
        if (toggle) {
            toggle.textContent = isOpen ? '▾' : '▸';
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.setAttribute('aria-label', isOpen ? 'Thu gọn phần' : 'Mở rộng phần');
        }
    };

    const enhanceSections = (root) => {
        root.querySelectorAll('[wire\\:key^="section-structure-"]').forEach((article) => {
            const index = sectionIndexFromArticle(article);
            if (index === null) return;

            const header = article.firstElementChild;
            if (!header) return;

            let toggle = article.querySelector('[data-request-section-toggle]');
            if (!toggle) {
                toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.dataset.requestSectionToggle = '1';
                toggle.className = 'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-base font-semibold text-slate-600 hover:border-indigo-300 hover:text-indigo-700';
                toggle.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const nextOpen = !openSections.has(index);
                    if (nextOpen) openSections.add(index);
                    else openSections.delete(index);
                    setSectionOpen(article, index, nextOpen);
                });
                header.prepend(toggle);
            }

            setSectionOpen(article, index, openSections.has(index));
        });
    };

    const enhanceFormColumns = (root) => {
        const form = root.querySelector('#request-designer-form');
        if (!form) return;

        const structureTitle = Array.from(form.querySelectorAll('h3')).find((heading) => heading.textContent.trim() === 'Cấu trúc biểu mẫu');
        const editorTitle = Array.from(form.querySelectorAll('h3')).find((heading) => heading.textContent.trim() === 'Thuộc tính trường');
        const structurePanel = structureTitle?.closest('div.min-w-0.rounded-xl');
        const editorPanel = editorTitle?.closest('div.min-w-0.rounded-xl');
        const workspaceGrid = structurePanel?.parentElement;
        if (!structurePanel || !editorPanel || !workspaceGrid) return;

        const desktop = window.matchMedia('(min-width: 1024px)').matches;
        if (desktop) {
            workspaceGrid.style.display = 'grid';
            workspaceGrid.style.gridTemplateColumns = 'minmax(0, 2fr) minmax(0, 3fr)';
            workspaceGrid.style.alignItems = 'start';
            structurePanel.style.display = 'block';
            editorPanel.style.display = 'block';
            structurePanel.style.minWidth = '0';
            editorPanel.style.minWidth = '0';
        } else {
            workspaceGrid.style.gridTemplateColumns = '';
        }
    };

    const clearDropState = (root) => {
        root.querySelectorAll('[data-request-drop-target="1"]').forEach((button) => {
            button.dataset.requestDropTarget = '0';
            button.classList.remove('ring-2', 'ring-indigo-300', 'border-indigo-400');
        });
    };

    const reorderField = async (root, source, target, dropAfter) => {
        const component = livewireComponent(root);
        if (!component) return;

        const current = component.get('sections');
        const sections = JSON.parse(JSON.stringify(current ?? []));
        const sourceFields = sections[source.section]?.fields;
        const targetFields = sections[target.section]?.fields;
        if (!Array.isArray(sourceFields) || !Array.isArray(targetFields) || !sourceFields[source.field]) return;

        const [moved] = sourceFields.splice(source.field, 1);
        let insertIndex = target.field + (dropAfter ? 1 : 0);
        if (source.section === target.section && source.field < insertIndex) insertIndex--;
        insertIndex = Math.max(0, Math.min(insertIndex, targetFields.length));
        targetFields.splice(insertIndex, 0, moved);

        openSections.add(target.section);
        const state = alpineState(root);
        if (state) {
            state.selectedSection = target.section;
            state.selectedField = insertIndex;
            state.formPane = 'editor';
        }

        await component.set('sections', sections);
    };

    const enhanceDragAndDrop = (root) => {
        root.querySelectorAll('[wire\\:key^="field-picker-"]').forEach((button) => {
            if (button.dataset.requestDndReady === '1') return;
            button.dataset.requestDndReady = '1';
            button.draggable = true;
            button.title = 'Kéo để sắp xếp trường';
            button.classList.add('cursor-move');

            const handle = button.querySelector('span[aria-hidden="true"]');
            if (handle) {
                handle.textContent = '⋮⋮';
                handle.title = 'Kéo để sắp xếp';
                handle.style.cursor = 'grab';
                handle.style.fontWeight = '700';
                handle.style.letterSpacing = '-2px';
            }

            button.addEventListener('dragstart', (event) => {
                const indexes = fieldIndexesFromButton(button);
                if (!indexes) return;
                draggedField = indexes;
                button.classList.add('opacity-50');
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', `${indexes.section}:${indexes.field}`);
                }
            });

            button.addEventListener('dragend', () => {
                draggedField = null;
                button.classList.remove('opacity-50');
                clearDropState(root);
            });

            button.addEventListener('dragover', (event) => {
                if (!draggedField) return;
                event.preventDefault();
                if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
                clearDropState(root);
                button.dataset.requestDropTarget = '1';
                button.classList.add('ring-2', 'ring-indigo-300', 'border-indigo-400');
            });

            button.addEventListener('dragleave', () => {
                button.dataset.requestDropTarget = '0';
                button.classList.remove('ring-2', 'ring-indigo-300', 'border-indigo-400');
            });

            button.addEventListener('drop', async (event) => {
                if (!draggedField) return;
                event.preventDefault();
                const target = fieldIndexesFromButton(button);
                if (!target) return;
                const rect = button.getBoundingClientRect();
                const dropAfter = event.clientY > rect.top + rect.height / 2;
                const source = draggedField;
                draggedField = null;
                clearDropState(root);
                await reorderField(root, source, target, dropAfter);
            });
        });
    };

    const approvalArticles = (approval) => Array.from(approval?.querySelectorAll('[wire\\:key^="stage-"]') ?? []);

    const activateApprovalStage = (approval, index) => {
        const articles = approvalArticles(approval);
        const tabs = Array.from(approval.querySelectorAll('[data-request-approval-tab]'));
        if (articles.length === 0) return;

        selectedApprovalStage = Math.max(0, Math.min(index, articles.length - 1));

        articles.forEach((article, articleIndex) => {
            article.hidden = articleIndex !== selectedApprovalStage;
        });

        tabs.forEach((button, tabIndex) => {
            const active = tabIndex === selectedApprovalStage;
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.className = active
                ? 'min-h-10 shrink-0 rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-800'
                : 'min-h-10 shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 hover:border-indigo-200 hover:text-indigo-700';
        });
    };

    const syncApprovalTabs = (approval) => {
        const articles = approvalArticles(approval);
        const tabList = approval.querySelector('[data-request-approval-tabs]');
        if (!tabList) return;

        if (articles.length === 0) {
            tabList.replaceChildren();
            return;
        }

        const signature = articles.map((article, index) => {
            const name = article.querySelector('.font-semibold.text-slate-900')?.textContent?.trim() || `Cấp ${index + 1}`;
            return `${index}:${name}`;
        }).join('|');

        if (tabList.dataset.requestApprovalSignature !== signature) {
            tabList.dataset.requestApprovalSignature = signature;
            tabList.replaceChildren();

            articles.forEach((article, index) => {
                const name = article.querySelector('.font-semibold.text-slate-900')?.textContent?.trim() || `Cấp ${index + 1}`;
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.requestApprovalTab = String(index);
                button.setAttribute('role', 'tab');
                button.textContent = `Cấp ${index + 1} · ${name}`;
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    activateApprovalStage(approval, index);
                });
                tabList.appendChild(button);
            });
        }

        activateApprovalStage(approval, selectedApprovalStage);
    };

    const setLabelText = (select, text) => {
        const label = select?.closest('label');
        if (!label) return;
        const textNode = Array.from(label.childNodes).find((node) => node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '');
        if (textNode) textNode.textContent = `${text}\n`;
    };

    const setApprovalEmailState = async (component, index, enabled) => {
        await component.set(`stages.${index}.email_on_assignment`, enabled);
        await component.set(`stages.${index}.email_on_decision`, enabled);
        await component.set(`stages.${index}.email_on_sla_warning`, enabled);
    };

    const enhanceApprovalBusinessUi = (root) => {
        const approval = root.querySelector('#request-designer-approval');
        const component = livewireComponent(root);
        if (!approval || !component) return;

        const articles = approvalArticles(approval);
        const stages = component.get('stages') ?? [];

        if (pendingNewStageEmailOff !== null && articles[pendingNewStageEmailOff] && stages[pendingNewStageEmailOff]) {
            const stageIndex = pendingNewStageEmailOff;
            pendingNewStageEmailOff = null;
            setApprovalEmailState(component, stageIndex, false);
        }

        articles.forEach((article, index) => {
            const modeSelect = article.querySelector(`select[wire\\:model\\.live="stages.${index}.mode"]`);
            const resolverSelect = article.querySelector(`select[wire\\:model\\.live="stages.${index}.resolver_key"]`);
            const approverSelect = article.querySelector(`select[wire\\:model="stages.${index}.resolver_user_ids"]`);

            if (modeSelect) {
                setLabelText(modeSelect, 'Chế độ phê duyệt');
                const labels = {
                    single: 'Một người phê duyệt',
                    parallel_all: 'Nhiều người – Tất cả phải duyệt',
                    parallel_any: 'Nhiều người – Một người duyệt là đủ',
                };
                Array.from(modeSelect.options).forEach((option) => {
                    if (labels[option.value]) option.textContent = labels[option.value];
                });

                if (modeSelect.dataset.requestApprovalModeReady !== '1') {
                    modeSelect.dataset.requestApprovalModeReady = '1';
                    modeSelect.addEventListener('change', () => {
                        if (modeSelect.value !== 'single') return;
                        queueMicrotask(() => {
                            const currentStages = component.get('stages') ?? [];
                            const ids = Array.from(currentStages[index]?.resolver_user_ids ?? []).map(Number).filter(Boolean);
                            if (ids.length > 1) component.set(`stages.${index}.resolver_user_ids`, [ids[0]]);
                        });
                    });
                }
            }

            if (resolverSelect) {
                setLabelText(resolverSelect, 'Cách xác định người phê duyệt');
                const labels = {
                    fixed_users: 'Chọn người phê duyệt',
                    role_members: 'Theo vai trò',
                    form_user_field: 'Theo trường trong biểu mẫu',
                };
                Array.from(resolverSelect.options).forEach((option) => {
                    if (labels[option.value]) option.textContent = labels[option.value];
                });
            }

            if (approverSelect) {
                setLabelText(approverSelect, 'Người phê duyệt');
                const mode = modeSelect?.value ?? stages[index]?.mode ?? 'single';
                const help = approverSelect.parentElement?.querySelector('span');
                if (help) {
                    help.textContent = mode === 'single'
                        ? 'Chế độ một người: chỉ được chọn 1 người phê duyệt.'
                        : 'Chế độ nhiều người: có thể chọn nhiều người phê duyệt.';
                }

                if (approverSelect.dataset.requestApproverReady !== '1') {
                    approverSelect.dataset.requestApproverReady = '1';
                    approverSelect.addEventListener('change', () => {
                        const currentMode = article.querySelector(`select[wire\\:model\\.live="stages.${index}.mode"]`)?.value ?? 'single';
                        if (currentMode !== 'single') return;

                        queueMicrotask(() => {
                            const latestStages = component.get('stages') ?? [];
                            const previous = Array.from(latestStages[index]?.resolver_user_ids ?? []).map(Number);
                            const selected = Array.from(approverSelect.selectedOptions).map((option) => Number(option.value)).filter(Boolean);
                            if (selected.length <= 1) return;
                            const newlySelected = selected.find((id) => !previous.includes(id));
                            const keep = newlySelected ?? selected[selected.length - 1];
                            component.set(`stages.${index}.resolver_user_ids`, [keep]);
                        });
                    });
                }
            }

            const emailPanelTitle = Array.from(article.querySelectorAll('div')).find((element) => element.textContent.trim() === 'Thông báo email');
            const emailPanel = emailPanelTitle?.parentElement;
            if (emailPanel && !emailPanel.querySelector('[data-request-email-master]')) {
                const master = document.createElement('label');
                master.dataset.requestEmailMaster = '1';
                master.className = 'mt-3 flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700';
                master.innerHTML = '<input type="checkbox" class="h-4 w-4 rounded border-slate-300"> Tắt toàn bộ email của cấp này';
                const checkbox = master.querySelector('input');
                const current = stages[index] ?? {};
                checkbox.checked = !current.email_on_assignment && !current.email_on_decision && !current.email_on_sla_warning;
                checkbox.addEventListener('change', () => setApprovalEmailState(component, index, !checkbox.checked));
                emailPanelTitle.nextElementSibling?.after(master) ?? emailPanel.appendChild(master);
            } else if (emailPanel) {
                const checkbox = emailPanel.querySelector('[data-request-email-master] input');
                const current = (component.get('stages') ?? [])[index] ?? {};
                if (checkbox && document.activeElement !== checkbox) {
                    checkbox.checked = !current.email_on_assignment && !current.email_on_decision && !current.email_on_sla_warning;
                }
            }
        });
    };

    const enhanceApprovalTabs = (root) => {
        const approval = root.querySelector('#request-designer-approval');
        if (!approval) return;

        const articles = approvalArticles(approval);
        const articlesContainer = articles[0]?.parentElement;
        if (!articlesContainer) return;

        let tabList = approval.querySelector('[data-request-approval-tabs]');
        if (!tabList) {
            tabList = document.createElement('div');
            tabList.dataset.requestApprovalTabs = '1';
            tabList.setAttribute('role', 'tablist');
            tabList.setAttribute('aria-label', 'Các cấp phê duyệt');
            tabList.className = 'mt-4 flex gap-2 overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 p-2';
            articlesContainer.before(tabList);
        }

        articlesContainer.classList.remove('space-y-4');
        articlesContainer.style.marginTop = '12px';

        const addButton = approval.querySelector('button[wire\\:click="addStage"]');
        if (addButton && addButton.dataset.requestApprovalAddReady !== '1') {
            addButton.dataset.requestApprovalAddReady = '1';
            addButton.addEventListener('click', () => {
                const nextIndex = approvalArticles(approval).length;
                selectedApprovalStage = nextIndex;
                pendingNewStageEmailOff = nextIndex;
            });
        }

        articles.forEach((article, index) => {
            article.style.marginTop = '0';
            const moveUp = article.querySelector(`button[wire\\:click="moveStage(${index}, -1)"]`);
            const moveDown = article.querySelector(`button[wire\\:click="moveStage(${index}, 1)"]`);
            const remove = article.querySelector(`button[wire\\:click="removeStage(${index})"]`);

            [moveUp, moveDown, remove].forEach((button) => {
                if (!button || button.dataset.requestApprovalActionReady === '1') return;
                button.dataset.requestApprovalActionReady = '1';
                button.addEventListener('click', () => {
                    if (button === moveUp) selectedApprovalStage = Math.max(0, index - 1);
                    if (button === moveDown) selectedApprovalStage = Math.min(approvalArticles(approval).length - 1, index + 1);
                    if (button === remove) selectedApprovalStage = Math.max(0, Math.min(index, approvalArticles(approval).length - 2));
                });
            });
        });

        syncApprovalTabs(approval);
    };

    const enhanceDesigner = () => {
        scheduled = false;
        const root = designerRoot();
        if (!root) return;

        root.querySelectorAll('[x-show].hidden').forEach((element) => {
            element.classList.remove('hidden');
        });

        enhanceSections(root);
        enhanceFormColumns(root);
        enhanceDragAndDrop(root);
        enhanceApprovalTabs(root);
        enhanceApprovalBusinessUi(root);

        if (!observer) {
            observer = new MutationObserver(() => {
                if (scheduled) return;
                scheduled = true;
                requestAnimationFrame(enhanceDesigner);
            });
            observer.observe(root, { childList: true, subtree: true });
        }
    };

    const boot = () => requestAnimationFrame(enhanceDesigner);

    document.addEventListener('DOMContentLoaded', boot, { once: true });
    document.addEventListener('livewire:init', boot);
    document.addEventListener('livewire:navigated', boot);
    window.addEventListener('load', boot, { once: true });
    window.addEventListener('resize', boot);
})();
</script>
@endsection
