@php
    $eventData = $event ?? $trackedEvent ?? session('tracked_event');
@endphp

@if (! empty($eventData))
    <script>
        (() => {
            const payload = @json($eventData);
            if (! payload || ! payload.event_id) {
                return;
            }

            const storageKey = 'tracked_evt_' + payload.event_id;
            try {
                if (sessionStorage.getItem(storageKey) || localStorage.getItem(storageKey)) {
                    return;
                }
                sessionStorage.setItem(storageKey, '1');
                localStorage.setItem(storageKey, '1');
            } catch (e) {}

            window.__tracked_events = window.__tracked_events || {};
            if (window.__tracked_events[payload.event_id]) {
                return;
            }
            window.__tracked_events[payload.event_id] = true;

            const eventId = payload.event_id;
            const eventName = payload.event_name;
            const customData = payload.custom_data || {};
            const dataLayerPayload = payload.datalayer || {};

            // 1. Google Tag Manager / GA4 dataLayer E-commerce Event
            window.dataLayer = window.dataLayer || [];
            if (dataLayerPayload.event) {
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: dataLayerPayload.event,
                    event_id: eventId,
                    ecommerce: dataLayerPayload.ecommerce || {}
                });
            }

            // 2. Meta Pixel Client Event with Deduplication EventID
            if (typeof fbq === 'function') {
                const fbData = {
                    content_type: 'product',
                    currency: customData.currency || 'BDT',
                    value: customData.value || 0
                };

                if (customData.content_name) {
                    fbData.content_name = customData.content_name;
                }

                if (customData.content_category) {
                    fbData.content_category = customData.content_category;
                }

                if (customData.contents && customData.contents.length > 0) {
                    fbData.contents = customData.contents;
                    fbData.content_ids = customData.content_ids || customData.contents.map(c => String(c.id || c.item_id));
                }

                if (customData.num_items) {
                    fbData.num_items = customData.num_items;
                }

                if (customData.order_id) {
                    fbData.order_id = customData.order_id;
                }

                fbq('track', eventName, fbData, { eventID: eventId });
            }

            // 3. TikTok Pixel Client Event with Deduplication event_id
            if (typeof ttq === 'object' && typeof ttq.track === 'function') {
                const tiktokEvent = eventName === 'Purchase' ? 'CompletePayment' : eventName;
                const ttContents = (customData.contents || []).map(item => ({
                    content_id: String(item.id || item.item_id || ''),
                    content_name: item.name || item.item_name || 'Product',
                    content_type: 'product',
                    quantity: Number(item.quantity || item.qty || 1),
                    price: Number(item.price || item.item_price || 0)
                }));

                const ttData = {
                    contents: ttContents,
                    value: customData.value || 0,
                    currency: customData.currency || 'BDT'
                };

                if (customData.order_id) {
                    ttData.order_id = customData.order_id;
                }

                ttq.track(tiktokEvent, ttData, { event_id: eventId });
            }
        })();
    </script>
@endif
