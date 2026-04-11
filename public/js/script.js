// Shared script loaded by all pages. Keep every feature defensive.
(function () {
    var slides = document.querySelector('.slides');
    var dotsContainer = document.querySelector('.dots');
    var prevBtn = document.getElementById('prev');
    var nextBtn = document.getElementById('next');
    var currentIndex = 0;
    var interval = null;

    function updateSlide(index) {
        if (!slides) {
            return;
        }

        slides.style.transform = 'translateX(-' + (index * 100) + '%)';
        document.querySelectorAll('.dot').forEach(function (dot, i) {
            dot.classList.toggle('active', i === index);
        });
    }

    function nextSlide() {
        if (!slides) {
            return;
        }

        currentIndex = currentIndex < slides.children.length - 1 ? currentIndex + 1 : 0;
        updateSlide(currentIndex);
    }

    function prevSlide() {
        if (!slides) {
            return;
        }

        currentIndex = currentIndex > 0 ? currentIndex - 1 : slides.children.length - 1;
        updateSlide(currentIndex);
    }

    function startAutoSlide() {
        interval = window.setInterval(nextSlide, 4000);
    }

    function resetInterval() {
        if (interval) {
            window.clearInterval(interval);
        }
        startAutoSlide();
    }

    function createDots() {
        if (!slides || !dotsContainer) {
            return;
        }

        for (var i = 0; i < slides.children.length; i++) {
            (function (dotIndex) {
                var dot = document.createElement('div');
                dot.classList.add('dot');
                dot.addEventListener('click', function () {
                    currentIndex = dotIndex;
                    updateSlide(currentIndex);
                    resetInterval();
                });
                dotsContainer.appendChild(dot);
            })(i);
        }
    }

    if (slides && dotsContainer && prevBtn && nextBtn) {
        prevBtn.addEventListener('click', function () {
            prevSlide();
            resetInterval();
        });

        nextBtn.addEventListener('click', function () {
            nextSlide();
            resetInterval();
        });

        createDots();
        updateSlide(currentIndex);
        startAutoSlide();
    }

    // Mobile menu fallback.
    var mobileMenu = document.getElementById('menu');
    var mobileMenuButton = document.querySelector('.menu-button');

    function setMobileMenuState(isOpen) {
        if (!mobileMenu) {
            return;
        }

        mobileMenu.classList.toggle('menu-open', !!isOpen);
        if (mobileMenuButton) {
            mobileMenuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var nextState = !mobileMenu.classList.contains('menu-open');
            setMobileMenuState(nextState);
        });

        document.addEventListener('click', function (event) {
            if (!mobileMenu.classList.contains('menu-open')) {
                return;
            }

            var clickedInsideMenu = mobileMenu.contains(event.target);
            var clickedButton = mobileMenuButton.contains(event.target);
            if (!clickedInsideMenu && !clickedButton) {
                setMobileMenuState(false);
            }
        });

        mobileMenu.addEventListener('click', function (event) {
            if (event.target && event.target.tagName === 'A') {
                setMobileMenuState(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && mobileMenu.classList.contains('menu-open')) {
                setMobileMenuState(false);
            }
        });
    }

    // Header notifications fallback (works even when inline handlers are blocked).
    document.addEventListener('click', function (event) {
        var notificationMenu = document.getElementById('notificationMenu');
        if (!notificationMenu) {
            return;
        }

        var trigger = notificationMenu.querySelector('.notification-trigger');
        if (!trigger) {
            return;
        }

        var clickedTrigger = event.target.closest('.notification-trigger');
        if (clickedTrigger) {
            event.preventDefault();
            var isOpen = notificationMenu.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            return;
        }

        if (!notificationMenu.contains(event.target)) {
            notificationMenu.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        var notificationMenu = document.getElementById('notificationMenu');
        if (!notificationMenu) {
            return;
        }

        var trigger = notificationMenu.querySelector('.notification-trigger');
        notificationMenu.classList.remove('is-open');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }
    });

    // Document modal fallback.
    function findDocModalParent(element) {
        var node = element;
        while (node && node !== document.body) {
            if (node.classList && node.classList.contains('doc-modal')) {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    }

    window.docModalOpen = function (modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }

        modal.removeAttribute('hidden');
        modal.style.display = 'flex';
        document.body.classList.add('doc-modal-open');
        document.body.setAttribute('data-active-doc-modal', modalId);
    };

    window.docModalClose = function (source) {
        var modal = null;

        if (typeof source === 'string') {
            modal = document.getElementById(source);
        } else {
            modal = findDocModalParent(source);
        }

        if (!modal) {
            return;
        }

        modal.setAttribute('hidden', 'hidden');
        modal.style.display = 'none';
        document.body.classList.remove('doc-modal-open');
        document.body.removeAttribute('data-active-doc-modal');
    };

    document.addEventListener('click', function (event) {
        var openBtn = event.target.closest('[data-doc-modal-open]');
        if (openBtn) {
            var modalId = openBtn.getAttribute('data-doc-modal-open');
            if (modalId) {
                window.docModalOpen(modalId);
            }
            return;
        }

        var closeBtn = event.target.closest('[data-doc-modal-close]');
        if (closeBtn) {
            window.docModalClose(closeBtn);
            return;
        }

        if (event.target.classList && event.target.classList.contains('doc-modal')) {
            window.docModalClose(event.target.id);
        }
    });

    // Property create image previews.
    var imageInput = document.getElementById('images');
    var previewContainer = document.getElementById('property-image-preview');

    if (imageInput && previewContainer) {
        var selectedFiles = [];
        var canSyncFileList = false;

        function createFileTransfer() {
            if (typeof DataTransfer === 'function') {
                try {
                    return new DataTransfer();
                } catch (error) {
                    // ignore
                }
            }

            try {
                var clipboardEvent = new ClipboardEvent('copy');
                if (clipboardEvent.clipboardData) {
                    return clipboardEvent.clipboardData;
                }
            } catch (error) {
                // ignore
            }

            return null;
        }

        canSyncFileList = !!createFileTransfer();

        function syncInputFiles() {
            var transfer = createFileTransfer();
            if (!transfer || !transfer.items || typeof transfer.items.add !== 'function') {
                canSyncFileList = false;
                return false;
            }

            selectedFiles.forEach(function (file) {
                transfer.items.add(file);
            });

            imageInput.files = transfer.files;
            canSyncFileList = true;
            return true;
        }

        function renderPreview() {
            previewContainer.innerHTML = '';

            if (!selectedFiles.length) {
                return;
            }

            var visibleEntries = [];
            selectedFiles.forEach(function (file, fileIndex) {
                if (file.type && file.type.indexOf('image/') === 0) {
                    visibleEntries.push({ file: file, fileIndex: fileIndex });
                }
            });

            if (!visibleEntries.length) {
                var noImageMsg = document.createElement('small');
                noImageMsg.className = 'dashboard-inline-note';
                noImageMsg.textContent = 'Nenhuma imagem válida selecionada.';
                noImageMsg.style.gridColumn = '1 / -1';
                previewContainer.appendChild(noImageMsg);
                return;
            }

            visibleEntries.forEach(function (entry, visibleIndex) {
                var file = entry.file;
                var fileIndex = entry.fileIndex;

                var card = document.createElement('div');
                card.className = 'property-image-thumb';

                if (visibleIndex === 0) {
                    var coverBadge = document.createElement('span');
                    coverBadge.className = 'property-image-cover-badge';
                    coverBadge.textContent = 'Capa';
                    card.appendChild(coverBadge);
                }

                var img = document.createElement('img');
                img.alt = file.name;

                var meta = document.createElement('small');
                var sizeMb = file.size / (1024 * 1024);
                meta.textContent = file.name + ' (' + sizeMb.toFixed(1) + ' MB)';

                var actions = document.createElement('div');
                actions.className = 'property-image-thumb-actions';

                var coverBtn = document.createElement('button');
                coverBtn.type = 'button';
                coverBtn.className = 'btn-secondary property-thumb-action-btn';
                coverBtn.textContent = 'Definir capa';
                coverBtn.disabled = visibleIndex === 0 || !canSyncFileList;
                coverBtn.addEventListener('click', function () {
                    if (visibleIndex === 0) {
                        return;
                    }

                    var chosen = selectedFiles[fileIndex];
                    selectedFiles.splice(fileIndex, 1);
                    selectedFiles.unshift(chosen);
                    if (!syncInputFiles()) {
                        return;
                    }
                    renderPreview();
                });

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn-secondary property-thumb-action-btn danger';
                removeBtn.textContent = 'Remover';
                removeBtn.disabled = !canSyncFileList;
                removeBtn.addEventListener('click', function () {
                    selectedFiles.splice(fileIndex, 1);
                    if (!syncInputFiles()) {
                        return;
                    }
                    renderPreview();
                });

                actions.appendChild(coverBtn);
                actions.appendChild(removeBtn);

                var reader = new FileReader();
                reader.onload = function (event) {
                    img.src = String(event.target && event.target.result ? event.target.result : '');
                };
                reader.readAsDataURL(file);

                card.appendChild(img);
                card.appendChild(meta);
                card.appendChild(actions);
                previewContainer.appendChild(card);
            });

            if (!canSyncFileList) {
                var warning = document.createElement('small');
                warning.className = 'dashboard-inline-note';
                warning.textContent = 'Seu navegador não permite remover/reordenar arquivos antes do envio.';
                warning.style.gridColumn = '1 / -1';
                previewContainer.appendChild(warning);
            }
        }

        imageInput.addEventListener('change', function () {
            selectedFiles = Array.prototype.slice.call(imageInput.files || []);
            if (canSyncFileList) {
                syncInputFiles();
            }
            renderPreview();
        });
    }

    // Referral links copy button.
    document.addEventListener('click', function (event) {
        var copyBtn = event.target.closest('.referral-copy-btn');
        if (!copyBtn) {
            return;
        }

        var targetId = copyBtn.getAttribute('data-copy-target');
        if (!targetId) {
            return;
        }

        var input = document.getElementById(targetId);
        if (!input) {
            return;
        }

        var value = input.value || '';
        if (!value) {
            return;
        }

        function markCopied() {
            copyBtn.classList.add('is-copied');
            copyBtn.setAttribute('title', 'Copiado');
            window.setTimeout(function () {
                copyBtn.classList.remove('is-copied');
                copyBtn.setAttribute('title', 'Copiar link');
            }, 1200);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(markCopied).catch(function () {
                input.select();
                document.execCommand('copy');
                markCopied();
            });
            return;
        }

        input.select();
        document.execCommand('copy');
        markCopied();
    });

    // Property detail gallery thumbnails.
    var mainImage = document.getElementById('property-main-image');
    var thumbsWrap = document.getElementById('property-gallery-thumbs');

    if (mainImage && thumbsWrap) {
        thumbsWrap.addEventListener('click', function (event) {
            var thumbBtn = event.target.closest('.property-gallery-thumb');
            if (!thumbBtn) {
                return;
            }

            var thumbImg = thumbBtn.querySelector('img');
            var nextImage = thumbBtn.getAttribute('data-gallery-image') || (thumbImg ? thumbImg.getAttribute('src') : '');
            if (!nextImage) {
                return;
            }

            mainImage.setAttribute('src', nextImage);

            var allThumbs = thumbsWrap.querySelectorAll('.property-gallery-thumb');
            Array.prototype.forEach.call(allThumbs, function (thumb) {
                thumb.classList.remove('is-active');
            });
            thumbBtn.classList.add('is-active');
        });
    }
})();