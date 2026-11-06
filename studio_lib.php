<?php
/* =====================================================================
 *  studio_lib.php — the studio mood photos (5 Nov 2026)
 *
 *  Photos of an artist's studio that give the site its atmosphere. They
 *  are MOOD photos only: never shown as artworks for sale, never as the
 *  work of an Oweili artist, never in the Gallery, the sitemap or as an
 *  artwork's share picture.
 *
 *  WHOLE photos (owner, 6 Nov 2026): every photo is shown whole, in its real
 *  shape, never cropped or zoomed. The layer is the size of the SCREEN (not
 *  the page), the photo is fitted inside it (object-fit: contain), and the
 *  space around it is filled with a soft blurred copy of the same photo (its
 *  tiny copy, blurred by the browser), so the main subject is never cut.
 *  Files: assets/img/studio/<key>-<width>.<avif|webp|jpg>, made once from the
 *  owner's originals in their own shape (compressed, hidden data removed).
 *  The browser picks the width it needs for the screen, and the lightest
 *  format it can show (AVIF, then WebP, then JPEG).
 *
 *    owStudioPhoto($key, $eager)   the <picture> (lazy unless $eager)
 *    owStudioHas($key)             true when the files exist
 *    owStudioBackdrop(...$keys)    the photo as the background of the WHOLE
 *                                  page (the first key whose files exist):
 *                                  a still layer behind everything with a soft
 *                                  veil (light and dark mode). It is a fixed
 *                                  layer, not background-attachment: fixed,
 *                                  so it also stays still on iPhone
 *
 *  Where (owner, 6 Nov 2026): hand painting the landscape → Gallery; brushes
 *  in jars → Join as an artist; hand on the palette → Commissions; paint
 *  buckets → Explore Art Styles; woman looking at framed paintings → Art
 *  Events, Artist Spotlights, Art Education, articles and Institutes; studio
 *  table → Support; pink palette → Sign in and the Join pages (and Gifts);
 *  paint-splattered cloth → the 404 page.
 * ===================================================================== */
require_once __DIR__ . '/ow_paths.php';

const OW_STUDIO = [
    // key => [widths made, original width, original height, alt EN, alt AR, tiny copy (for the blurred fill)]
    'home-painting' => [[480, 720, 1080], 1333, 2000,
        'An artist painting a sunny landscape on an easel outdoors',
        'فنانة ترسم منظراً طبيعياً مشمساً على حامل لوحات في الهواء الطلق',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAkABgDASIAAhEBAxEB/8QAGAABAQEBAQAAAAAAAAAAAAAAAAYEBQf/xAApEAABAwMCBQMFAAAAAAAAAAABAgMRAAQSBSEGEzFBUWFxgQcikcHh/8QAGAEAAwEBAAAAAAAAAAAAAAAAAAIEAwH/xAAfEQACAwAABwAAAAAAAAAAAAABAgADEQQTFCExQVP/2gAMAwEAAhEDEQA/AOfwtpFrrFi4+9zUKQoJBQQB032qhRw5bo2D9wQPVJ/VY/puUOaC6gxKX1fMgVWuraYbyWYTMSRUoNoclWyalamQB12T7fDjInN95ficRH4FK1v6tDxRbtpVBiZ2WI6g0oN1wOcyJ0/D/MSC4Av2rbVlsXCjy304pTjlK+o29gasNVuHCpvFuGHCUllOIJHlR7D29q8ubWhDwUgqRj0V3mutY687YuI5k3DY7FX3J9UnsfiK2essNEK7QpxpQa5f3NheN3Ftb8qzMgpyjJUCTHbaB/aVnvNZt9S1VhpTwOnOMqSptIjAqSTHmZjfzSkWrR3Ed7QD5km4AUbgbCs1KVSZIJpsU5PgEncgbUpSurBvU//Z'],
    'brushes-jars' => [[480, 720, 1080], 1333, 2000,
        'Well-used paint brushes standing in glass jars in a studio',
        'فُرَش رسم مستعملة في برطمانات زجاجية داخل مرسم',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAkABgDASIAAhEBAxEB/8QAGgABAAMAAwAAAAAAAAAAAAAAAAQFBgECB//EACgQAAIBAwMDBAIDAAAAAAAAAAECAwAEEQUSITFBcRMiUWEVgZHR8P/EABgBAAMBAQAAAAAAAAAAAAAAAAABAgME/8QAGxEBAAIDAQEAAAAAAAAAAAAAABEhAQIxQVH/2gAMAwEAAhEDEQA/ANfcDEFR0Ax5qutdZje3FveMI5UUEt0XqcD5zxUtLiJmKLIu4EAj7PIrmlpGcO0yLtOR90rmRvafmlMPPItTNzdRR+ju6kjdy2MkCpejajfX12sBGI2xvYdl7jzxU2K1EGvG2S3VoowPTfZhmQ4Bb9c9qq7Jb2PU9lnC0ZeQRlmXIAzjJ+OKqvBLTflhHblAAXXK5c45xlQQP9xSqxtHMkk+ZdodiN3XkNnnnzSs7+rppLH2ahckDLJGoDHk43N3qSLO3vLpbu4j3TKNqnJGP480pRrxG/VFqMIi1kwIzbBIG5OTk4/ulKVRv//Z'],
    'paint-cloth' => [[480, 720, 1080], 1499, 2000,
        'A paint-splattered cloth with bowls of colour and brushes',
        'قماش مرشوش بالألوان مع أوعية الألوان والفُرَش',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABgDASIAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAABgAFA//EAC0QAAIBAgUDAgQHAAAAAAAAAAECAwQRAAUSITEiQXETIwYUQrFRgZGhwdHw/8QAGAEAAgMAAAAAAAAAAAAAAAAAAgQAAQP/xAAfEQACAQQCAwAAAAAAAAAAAAABAgADERIxIUEEIqH/2gAMAwEAAhEDEQA/AF6zozW6trA7WA/PBzNs5lZ7UzelToWSSSUlN15t9t/xGEBX3UZj7gU7jgD/ABxm/EFBSzUrSywo85GhC56bkjkd+MAbbPUJb3svcGzVMs9qmlmYCNtQLjWLggkMOdP9fpYQZdQZW0c9EaSRZQ5uoc9INrG4237d8WNUq06gy5+SVUNJsBxNuVWqqR1grHgLAH1Yt7AH6b+P3wWzLN2zXNYqWmWUxOpTS5AU77sNubYQTT6culkQx6YtYu03SQB3P8YKzF6Q6ooYBVaRrZLkLq4C342I84DElWC7tLQhWDHV5tZFB8rPJRxkEuQzsRZivHUPt5xY50NHU5bOjKTJPLCyu0rbFgb6fF+/jFhWgjBfbcY8psmBE//Z'],
    'pink-palette' => [[640, 960, 1280], 1500, 1125,
        'A wooden palette with pink and orange paint beside paint brushes',
        'لوحة ألوان خشبية بألوان وردية وبرتقالية بجانب فُرَش الرسم',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACADASIAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAABQAEBv/EACUQAAIBAwQCAQUAAAAAAAAAAAECAwAEEQUSIVExQXEiMmGhsf/EABgBAAMBAQAAAAAAAAAAAAAAAAACBAED/8QAHREAAgICAwEAAAAAAAAAAAAAAAECAyEiBBESMf/aAAwDAQACEQMRAD8A7MGgdcvXJmto2KtwBilYL22nlaKOUGRWK7TwSR5x3QuswGfUWNvFul2gHB5bAzjHeKS1SktWdeN5jZsczLei3RndSSp+0Hkmkop1e0Goby1s4yCfR9jHdY9Vtr+Oe2mtrciSOQES427cVgMEt80jwkIUJaS3ORl/ZAHAJA+D+qE+nkps5E1LAzeZ0+0utV0pXixIsSZO7cCfqYZ8ZP8AKrB7trPaYmhljZlQxsTkg528+D7B+e6qqH8IUxO31WKZ99y4KSrsuEI46Dj8ej1Qd/aXGn69smZnhly0TqoJdccr8jr3VVWLMR3rZ0j/2Q=='],
    'paint-buckets' => [[640, 1280, 1920], 2000, 1125,
        'A hand dipping a brush into buckets of colourful paint, seen from above',
        'يد تغمس فرشاة في علب ألوان زاهية، من الأعلى',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACsDASIAAhEBAxEB/8QAGQAAAwEBAQAAAAAAAAAAAAAAAAQFAgMG/8QAKhAAAgEDAwIGAQUAAAAAAAAAAQIDBAURABIhMVEGExQiQWFCgZGx0eH/xAAXAQADAQAAAAAAAAAAAAAAAAAAAgMB/8QAHhEBAAMBAAIDAQAAAAAAAAAAAQACAyEREjFBUfD/2gAMAwEAAhEDEQA/AONyvVsioEprtA7SgYjeIDcB350pDPUwURqqTFVAISIg/HHXkfXPGrVw8G01ekfrap45xkIExnjqOevfUK5yT+GqympoI3NEYipMnJc9O2ARgY1O2fmvZYuD34k2erluVSrysnnqm1SBhdo5x/umqK7R2qtglnjMh2suxCMbTwTn9NJ1CMtWrQU7hnUY8obhu+Rx01Qjt1PVVCpXRMrKSQAShUdj31psZZ+rwi2xNdfep2eqirGum6CSnAo5FATnLSAgHP1jP7jUqqsNYKhxTyKsQPtEh92PvWYbhPZ630kssPl59pI27VI+O3GNVvUI3uguNOIzyu45OqL+RM2llLfX7GLg1JDOssdOsgTGJFc5QnjP38a53pjLalhgMb1KsNvIyO550aNagIxCzYsL/diVsZKQLW1oWmYIVkyMdvcdF1u9tuDwS0kizGPIMgXjqO4ydGjSbBZSNgpWvZCKSXS81jMVSJDhA35DIwSeuT/XGsTeHC8zstwjALE4aM5/nRo0efqFs6vWf//Z'],
    'palette-hand' => [[480, 720, 1080], 1503, 2000,
        'A hand holding a paint palette in a studio',
        'يد تمسك لوحة الألوان داخل مرسم',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABgDASIAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAABgAFA//EACcQAAICAgAGAgEFAAAAAAAAAAECAxEABAUSEyExQQZRYTJScYHR/8QAFwEAAwEAAAAAAAAAAAAAAAAAAAECA//EABsRAQACAgMAAAAAAAAAAAAAAAEAERIxAiFB/9oADAMBAAIRAxEAPwDlofOp1dYtxV6YNGV7ZiPfZfebmr8u0eIbKwao2Hvy/RIRf5PrAfBzwZ4nSeGWeV6sMgbkFdz5+/rvmxwfb2Ejk14dUKgt0RFPj2T9YDW4YqdRjt7MnLC8bN+kkcho3f8AmWF5ZtzahilEYQVyLIh7190SO2WQ3fU044BTuDODkx7RJJVStMVHevxm1KsPRPSlld1NiPwL80Rfi/OH162m46iuosqQw7gjzmhrk6+lHxKV7TYlaJlu2517lgP20R/ePkPkkTTEku5Hpab6mtJsO2xHzEpJUayEUVFCyAcsMbnE+qnT1VlQ0QZGqyPwPWWMuJxfJ//Z'],
    'gallery-visit' => [[640, 1280, 1920], 2000, 1333,
        'A woman looking at framed paintings in a gallery',
        'امرأة تتأمل لوحات مؤطرة في معرض',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACQDASIAAhEBAxEB/8QAGQABAAMBAQAAAAAAAAAAAAAAAAQFBgID/8QAKRAAAgEDAgUCBwAAAAAAAAAAAQIDAAQRBSESExRBUTFhBiIyQnFy4f/EABcBAAMBAAAAAAAAAAAAAAAAAAABAwT/xAAcEQADAAEFAAAAAAAAAAAAAAAAAQIDERMhMVH/2gAMAwEAAhEDEQA/AMJbtcyMFjlkyfDGrKK0vmZR1MmD3Dk15aV8rGRIwxUZAOd9/ar2J55LTp7d1M8w2T6WDAbDPb0qVPkolwRrjSryGNWN7KoPdnOKq7+K9t/W6kcfvU+0utSubWWe5iaaCN+UQX+7I2x7DvXWoxpGzcChuo4WJ4TtuaOmPtGaaSVmyzsT+aVIaMB2HvSqaoloaL4ejsYHEk1wwbwBWo6nTuNXEpJHjGaUrJVNM3LHLRV6ALSytpkYuheYvjIOdh/an6jNZ3UHL5zjI80pSeSmCxSjNNoKsxZLlcE96UpS3K9Dbnw//9k='],
    'studio-table' => [[640, 1280, 1920], 2000, 1333,
        'A studio table with brushes and paints',
        'طاولة مرسم عليها فُرَش وألوان',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACQDASIAAhEBAxEB/8QAGQAAAwEBAQAAAAAAAAAAAAAAAAMGBQEH/8QAKRAAAQMDAwMDBQEAAAAAAAAAAQIDBAARIRITMQVBYQYiMiNRgaGxcf/EABcBAAMBAAAAAAAAAAAAAAAAAAABAwT/xAAdEQACAgIDAQAAAAAAAAAAAAAAAQIRAyESMUEi/9oADAMBAAIRAxEAPwC66gsCPzzj9GsppaeoKafjn2N2Cr845FP6q+rbbYSAHXb6So2AAwfzmpeNKkdNQUuPFWyEK0tDCgRnP2xYmpy1tDS5aZZQ2W0uJcAIS4j4nt3p6jo1q2gQDYWH6/lSfVPUDceFGWl16Opa9SQkg+0Yzfsb5/yuzvULnUVRobF4+6dzeQcoQBcq8d8+KL9GovqirjOOOtcDWglCwM2UDRXn3T/VEtthZbWXS46tal2vqN+fGAMUU7aDjZreqZTEpyFDS59QrWoHgJUACL357Y81g9McfeTPZ206nFJ0t6LgEcjPn+0UUZFT0GKVx2Ol9BkzGkIVoQppo7TSkatRvfm/t+1KhlSmX2fgqLHcbtf4kAkAkfYk+M0UVlyXjxpxfpVfUmmT8qMqC4lqRtx1FCVBO4ci1r/kg0UUVrIro//Z'],
];
/* The tiny first frames of the homepage videos (their blurred fill, index.php) */
const OW_HERO_TINY = [
    'blue' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACsDASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAQFBgP/xAAtEAABBAECBAUCBwAAAAAAAAABAgMEEQASIQUiQXETMVFhgQbBFCMyUpGh8f/EABcBAQADAAAAAAAAAAAAAAAAAAQCAwX/xAAgEQEAAgIBBAMAAAAAAAAAAAABAAIDESEEMbHBQXGB/9oADAMBAAIRAxEAPwDOz3i9ORJW5+YtRC7BvpR7CumahnRNgBx5NOEfqP7huCD3+4yC5HbW4l5CtYsahV6c0koI8FtLPM2dPkLsD/MQXq4td3xxKb4slMmnj3I8gh9Eh94FLugG/PUq+Ue3Qb5Qej/gYLUqVHE5shKyG1cqCE1Y9e+JR2Q/4rJ0alSSkFQ/i+222X+GsvtOSGp61LQ84QkrIoAUE0Pceu9jDdTdpUB/Jf0oWstiI8R1Ro0eVFBC+Vzw627HF0ccacQFqRoJG6QLr5yvxSMUw3EN9EEJH2zAupdbdUgpUK6VhsWS1jlis2OrpJ04fLgxjUl5xKyKV5iva8pj6jiOrbYYDhQLFkeSiRVH0wwxthbbWEE1rUowtCOJJSW7Jc1rPfric6U7NjJSsrDaylYUCQCCVH4rYYYZAqWS6cge5Oi1rao/L6luFPcUlmPNOpxabSopr4PvVHOEvhbK5K12d6P9YYZm9nZNB4NfXif/2Q==',
    'pink' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACsDASIAAhEBAxEB/8QAGgAAAwADAQAAAAAAAAAAAAAAAAQFAQIDBv/EACgQAAICAgAEBAcAAAAAAAAAAAECAAMEEQUSITEyUmFxBhMiI0FRkv/EABkBAAIDAQAAAAAAAAAAAAAAAAMEAQIFBv/EAB4RAAICAgIDAAAAAAAAAAAAAAABAhEDIQQSIkFh/9oADAMBAAIRAxEAPwBe7h+NawVVC2N5ekTs4PbWdowYfo9DKGM2rGvfxEaAm12fXWQr65ieg30A94rVr6dH16PxRLahkGnUg+s35Aa+stvh2W0Cw8oB7AzkvCLbF+kDRG97lkpLVA3lxyV2eacAPMbWWsr4faqs22ZKADX4ig4fhEdeIJ/QhoIzOQ92izRV9sb7mL5PCq81yGYqzkBTrfLCErSo0HOVMbyPn1U1YtjAitApPmieQeWve2767kdIQiM5yeem/YXjxXSOiPfleIAkj1masxBWAa037CEJKQ5LR//Z',
    'pink-tall' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAuABgDASIAAhEBAxEB/8QAGgAAAwADAQAAAAAAAAAAAAAAAAIFAQMEBv/EACsQAAIBAwEGBQUBAAAAAAAAAAECAwAEERIFEyEiMUEUI1FxsTNSYYGh4f/EABcBAQEBAQAAAAAAAAAAAAAAAAQCAwb/xAAfEQACAgIDAAMAAAAAAAAAAAABAgADESESIkEEcbH/2gAMAwEAAhEDEQA/AJz7GV8m2k4KcEN60vhJIWUSDHtVNreXwwEL6G7E9q3c0UMMc8CGZV8wk5BP4ozdByM6AEL0UanmLlEWYg0VTvdoxBtC21ujBhzBMn+0Va2qNQl3xLXbIH5KaSK7FVYcvWum6dNMRPUr8VMtAIpGKjlYZ696eeY7tkVgCrA5PYHtWYdLF+4w1dhjyRdsQ+ZvE/Y9BRXdtCMbpsnjjGKKyY4YiLADDMzFOp0445GadYjPE8anBZdR985HxUqwl1WyE9QMVYtG5df3H/KjAqTUjOsiKYWuZCScR4B96K3wt9RuxIUD0FFIrr5LyPswe0g4E//Z',
];

function owStudioFile(string $key, int $w, string $ext): string {
    return 'assets/img/studio/' . $key . '-' . $w . '.' . $ext;
}

function owStudioHas(string $key): bool {
    return isset(OW_STUDIO[$key]) && is_file(__DIR__ . '/' . owStudioFile($key, OW_STUDIO[$key][0][0], 'jpg'));
}

/* The whole photo, fitted inside the screen. The browser is told how wide it really shows:
   the screen width, or less when the screen is wider than the photo's shape (sizes). */
function owStudioPhoto(string $key, bool $eager = false, string $class = 'ow-studio'): string {
    if (!owStudioHas($key)) return '';
    [$widths, $W, $H, $altEn, $altAr] = OW_STUDIO[$key];
    $b = owBase();
    $set = fn(string $ext): string => implode(', ', array_map(fn($w) => $b . owStudioFile($key, $w, $ext) . ' ' . $w . 'w', $widths));
    $sizes = 'min(100vw, ' . round(100 * $W / $H, 1) . 'vh)';
    $h = '<picture class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
    foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $ext => $mime) $h .= '<source type="' . $mime . '" srcset="' . $set($ext) . '" sizes="' . $sizes . '">';
    $h .= '<img src="' . $b . owStudioFile($key, $widths[0], 'jpg') . '" srcset="' . $set('jpg') . '" sizes="' . $sizes . '"'
        . ' width="' . $W . '" height="' . $H . '" decoding="' . ($eager ? 'sync' : 'async') . '"'
        . ($eager ? ' fetchpriority="high"' : ' loading="lazy"')
        . ' alt="' . htmlspecialchars($altEn . ' · ' . $altAr, ENT_QUOTES, 'UTF-8') . '">';
    return $h . '</picture>';
}

/* The blurred fill: the photo's tiny copy as a CSS value (--ow-tiny), blurred by oweili.css */
function owStudioTinyVar(string $key): string {
    $t = OW_STUDIO[$key][5] ?? (OW_HERO_TINY[$key] ?? '');
    return $t === '' ? '' : '--ow-tiny:url(' . $t . ')';
}

/* The photo behind the whole page. Printed right after <body>. A layer the size of the screen,
   fixed (it never grows with a long page): the blurred fill, then the whole photo on top.
   The body becomes see-through so the layer shows behind every section. */
function owStudioBackdrop(string ...$keys): string {
    foreach ($keys as $k) {
        if (!owStudioHas($k)) continue;
        return '<style>html body{background:transparent!important}</style><div class="ow-backdrop ow-fill" style="' . owStudioTinyVar($k) . '">'
             . owStudioPhoto($k, true, 'ow-studio') . '</div>';
    }
    return '';
}
