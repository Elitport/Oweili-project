<?php
/* =====================================================================
 *  studio_lib.php — the studio mood photos (5 Nov 2026)
 *
 *  Photos of an artist's studio that give the site its atmosphere. They
 *  are MOOD photos only: never shown as artworks for sale, never as the
 *  work of an Oweili artist, never in the Gallery, the sitemap or as an
 *  artwork's share picture.
 *
 *  FULL-SCREEN photos (owner, 6 Nov 2026, second version): every photo fills
 *  the whole screen again (object-fit: cover), but the layer is the size of
 *  the SCREEN, never the page, so a long page never zooms it in; and each
 *  photo keeps its main subject in view (OW_STUDIO_FOCUS). No blurred fill.
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
 *  buckets → Art Styles; woman looking at framed paintings → Art
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
    'pink-palette' => [[640, 960, 1280, 1500], 1500, 1125,
        'A wooden palette with pink and orange paint beside paint brushes',
        'لوحة ألوان خشبية بألوان وردية وبرتقالية بجانب فُرَش الرسم',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACADASIAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAABQAEBv/EACUQAAIBAwQCAQUAAAAAAAAAAAECAwAEEQUSIVExQXEiMmGhsf/EABgBAAMBAQAAAAAAAAAAAAAAAAACBAED/8QAHREAAgICAwEAAAAAAAAAAAAAAAECAyEiBBESMf/aAAwDAQACEQMRAD8A7MGgdcvXJmto2KtwBilYL22nlaKOUGRWK7TwSR5x3QuswGfUWNvFul2gHB5bAzjHeKS1SktWdeN5jZsczLei3RndSSp+0Hkmkop1e0Goby1s4yCfR9jHdY9Vtr+Oe2mtrciSOQES427cVgMEt80jwkIUJaS3ORl/ZAHAJA+D+qE+nkps5E1LAzeZ0+0utV0pXixIsSZO7cCfqYZ8ZP8AKrB7trPaYmhljZlQxsTkg528+D7B+e6qqH8IUxO31WKZ99y4KSrsuEI46Dj8ej1Qd/aXGn69smZnhly0TqoJdccr8jr3VVWLMR3rZ0j/2Q=='],
    'paint-buckets' => [[640, 1280, 1920, 2000], 2000, 1125,
        'A hand dipping a brush into buckets of colourful paint, seen from above',
        'يد تغمس فرشاة في علب ألوان زاهية، من الأعلى',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACsDASIAAhEBAxEB/8QAGQAAAwEBAQAAAAAAAAAAAAAAAAQFAgMG/8QAKhAAAgEDAwIGAQUAAAAAAAAAAQIDBAURABIhMVEGExQiQWFCgZGx0eH/xAAXAQADAQAAAAAAAAAAAAAAAAAAAgMB/8QAHhEBAAMBAAIDAQAAAAAAAAAAAQACAyEREjFBUfD/2gAMAwEAAhEDEQA/AONyvVsioEprtA7SgYjeIDcB350pDPUwURqqTFVAISIg/HHXkfXPGrVw8G01ekfrap45xkIExnjqOevfUK5yT+GqympoI3NEYipMnJc9O2ARgY1O2fmvZYuD34k2erluVSrysnnqm1SBhdo5x/umqK7R2qtglnjMh2suxCMbTwTn9NJ1CMtWrQU7hnUY8obhu+Rx01Qjt1PVVCpXRMrKSQAShUdj31psZZ+rwi2xNdfep2eqirGum6CSnAo5FATnLSAgHP1jP7jUqqsNYKhxTyKsQPtEh92PvWYbhPZ630kssPl59pI27VI+O3GNVvUI3uguNOIzyu45OqL+RM2llLfX7GLg1JDOssdOsgTGJFc5QnjP38a53pjLalhgMb1KsNvIyO550aNagIxCzYsL/diVsZKQLW1oWmYIVkyMdvcdF1u9tuDwS0kizGPIMgXjqO4ydGjSbBZSNgpWvZCKSXS81jMVSJDhA35DIwSeuT/XGsTeHC8zstwjALE4aM5/nRo0efqFs6vWf//Z'],
    'palette-hand' => [[480, 720, 1080], 1503, 2000,
        'A hand holding a paint palette in a studio',
        'يد تمسك لوحة الألوان داخل مرسم',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABgDASIAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAABgAFA//EACcQAAICAgAGAgEFAAAAAAAAAAECAxEABAUSEyExQQZRYTJScYHR/8QAFwEAAwEAAAAAAAAAAAAAAAAAAAECA//EABsRAQACAgMAAAAAAAAAAAAAAAEAERIxAiFB/9oADAMBAAIRAxEAPwDlofOp1dYtxV6YNGV7ZiPfZfebmr8u0eIbKwao2Hvy/RIRf5PrAfBzwZ4nSeGWeV6sMgbkFdz5+/rvmxwfb2Ejk14dUKgt0RFPj2T9YDW4YqdRjt7MnLC8bN+kkcho3f8AmWF5ZtzahilEYQVyLIh7190SO2WQ3fU044BTuDODkx7RJJVStMVHevxm1KsPRPSlld1NiPwL80Rfi/OH162m46iuosqQw7gjzmhrk6+lHxKV7TYlaJlu2517lgP20R/ePkPkkTTEku5Hpab6mtJsO2xHzEpJUayEUVFCyAcsMbnE+qnT1VlQ0QZGqyPwPWWMuJxfJ//Z'],
    'gallery-visit' => [[640, 1280, 1920, 2000], 2000, 1333,
        'A woman looking at framed paintings in a gallery',
        'امرأة تتأمل لوحات مؤطرة في معرض',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACQDASIAAhEBAxEB/8QAGQABAAMBAQAAAAAAAAAAAAAAAAQFBgID/8QAKRAAAgEDAgUCBwAAAAAAAAAAAQIDAAQRBSESExRBUTFhBiIyQnFy4f/EABcBAAMBAAAAAAAAAAAAAAAAAAABAwT/xAAcEQADAAEFAAAAAAAAAAAAAAAAAQIDERMhMVH/2gAMAwEAAhEDEQA/AMJbtcyMFjlkyfDGrKK0vmZR1MmD3Dk15aV8rGRIwxUZAOd9/ar2J55LTp7d1M8w2T6WDAbDPb0qVPkolwRrjSryGNWN7KoPdnOKq7+K9t/W6kcfvU+0utSubWWe5iaaCN+UQX+7I2x7DvXWoxpGzcChuo4WJ4TtuaOmPtGaaSVmyzsT+aVIaMB2HvSqaoloaL4ejsYHEk1wwbwBWo6nTuNXEpJHjGaUrJVNM3LHLRV6ALSytpkYuheYvjIOdh/an6jNZ3UHL5zjI80pSeSmCxSjNNoKsxZLlcE96UpS3K9Dbnw//9k='],
    'studio-table' => [[640, 1280, 1920, 2000], 2000, 1333,
        'A studio table with brushes and paints',
        'طاولة مرسم عليها فُرَش وألوان',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACQDASIAAhEBAxEB/8QAGQAAAwEBAQAAAAAAAAAAAAAAAAMGBQEH/8QAKRAAAQMDAwMDBQEAAAAAAAAAAQIDBAARIRITMQVBYQYiMiNRgaGxcf/EABcBAAMBAAAAAAAAAAAAAAAAAAABAwT/xAAdEQACAgIDAQAAAAAAAAAAAAAAAQIRAyESMUEi/9oADAMBAAIRAxEAPwC66gsCPzzj9GsppaeoKafjn2N2Cr845FP6q+rbbYSAHXb6So2AAwfzmpeNKkdNQUuPFWyEK0tDCgRnP2xYmpy1tDS5aZZQ2W0uJcAIS4j4nt3p6jo1q2gQDYWH6/lSfVPUDceFGWl16Opa9SQkg+0Yzfsb5/yuzvULnUVRobF4+6dzeQcoQBcq8d8+KL9GovqirjOOOtcDWglCwM2UDRXn3T/VEtthZbWXS46tal2vqN+fGAMUU7aDjZreqZTEpyFDS59QrWoHgJUACL357Y81g9McfeTPZ206nFJ0t6LgEcjPn+0UUZFT0GKVx2Ol9BkzGkIVoQppo7TSkatRvfm/t+1KhlSmX2fgqLHcbtf4kAkAkfYk+M0UVlyXjxpxfpVfUmmT8qMqC4lqRtx1FCVBO4ci1r/kg0UUVrIro//Z'],
    // the owner's photos for Art Education and Art Institutes (6 Nov 2026)
    'art-book' => [[480, 720, 1080], 1500, 2000,
        'A hand turning the pages of an art book beside a glass of tea',
        'يد تقلّب صفحات كتاب فني بجانب كوب شاي',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAgABgDASIAAhEBAxEB/8QAFwAAAwEAAAAAAAAAAAAAAAAAAAUGBP/EACkQAAIBAwMCBAcAAAAAAAAAAAECAwAEERIhMQUTQVFhgSIjMjNCcpH/xAAWAQEBAQAAAAAAAAAAAAAAAAACAwH/xAAYEQEBAQEBAAAAAAAAAAAAAAABABEhAv/aAAwDAQACEQMRAD8Ay9JWKW7EUsWpMEeI3pi1q0E+lpYo4y2IzK4Gry2pIvV5ReJaSzZ+PICjCjG3HhW3qjx/fADsycfkQDjn0qZ5m+pxDOYsoTpZdiPWilxnM0MVw65dxpk/ddj/AHY+9FZuWhtG2EVthpZbiSSVk2RV+k54yfTfyqghvOnXENuhurhZbdiSiRBtQI4ydh71G28vbk1OoKnnOa1hgiiFh8ptzjk00gdq4XEKxdmC3WGJm1kmTuMTjHPA9qKSWOIINCA6fDJzRUnrWOF//9k='],
    'easels-class' => [[640, 1280, 1920, 2000], 2000, 1297,
        'Easels with blank canvases ready for an art class in a studio',
        'حوامل رسم عليها لوحات بيضاء جاهزة لدرس فني في مرسم',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACUDASIAAhEBAxEB/8QAGQABAQADAQAAAAAAAAAAAAAAAAYDBQcE/8QAKRAAAgEEAgECBQUAAAAAAAAAAQIDAAQRIQUSBkFREyIxcYEVFjKR8f/EABcBAQEBAQAAAAAAAAAAAAAAAAIDBAD/xAAaEQADAQEBAQAAAAAAAAAAAAAAAQIRAzFB/9oADAMBAAIRAxEAPwAvk9/b+L2nJKYWkkuJI37x6wBrQqy8a5F+W4eK8lVQ5d1IUYGjiuZC3vJfBLKGC1mklW/c9FjJOOujj2qx8f5Acfx9rbohZZrqZWCgkx52M/nVOmSn6WBkVXCEHJqf/c9lNyV/YPBMj2h6FyOwc56gADeyayzcjLDPYmRAxnZlYYx0AXI/Oc1O3U0kNzLeGOJQ0gkcAYLFJD1OceqnH9VywT02lxyXErHDNI+p07qfhtsfT21SofykKZLaDqesKMF+bGif9pTXRZ4F8nvpa/qb20zxk/wbGTs1ruCWSCS5kmSMo83dO2/qdnH2pSlUp4Z4trUb8hQmX+f13Xg5J4BC6rH99416YP3pSjRaDn3OSMs6dCSN4yckDWMn3pSlRRoZ/9k='],
    // the owner's wide photos for Art Events and Art Education (6 Nov 2026)
    'museum-walk' => [[640, 1280, 1920, 2000], 2000, 1331,
        'Visitors walking through a museum hall with carved stone reliefs on red walls',
        'زوار يتجولون في قاعة متحف بين نقوش حجرية على جدران حمراء',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACQDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwCHR55dW8L3NjMFUmIsJAh4x6/lXNadd3NuJY4cJvK/vMZIzjFTxW+npoUs+JDcjhcyHA/AVkxzPLNBEwMiKyYQH73I4rmhGLvY7arnBLmZ1WuWN5ZRRxC+ju47kDGVwY2yBg/n+hrN06KzFzPc3EjPtuliCrxkcknP1GK2tavDKlzHtEMUzLIFMwcJKvPb1/xrjbq+N2VV41AjG0HFNJ30RPtdNXqaF9dwyX9wZnAIkYKMdFB46UVhNtJ9Pb0orTkRH1mS6Gjb3I8jydpYt27VY/suecKYYAAR17ZoorCrJw2Lp/vF7xUtrOd7iWONf3kZwecc5xTJ7C5ikO9GDE5IIxRRT9rLnsRGlFxuQtA+eV5FFFFaqTIcFc//2Q=='],
    // the owner's photo for Art Events (7 Nov 2026): a concrete hall with posters, a glass roof and cafe tables
    'events-hall' => [[640, 1280, 1920, 2000], 2000, 1333,
        'A bright concrete hall with art posters on the wall, a glass roof and cafe tables',
        'قاعة خرسانية مضيئة بملصقات فنية على الجدار وسقف زجاجي وطاولات مقهى',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACQDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDibaGSdtsSFj39B9a6DT9PhtyHlxJIOR6D6Cs7TLvZbYDr+8Ubh6VqpdKcMzqAMc54p3MZXuaKKAh2jDZyD6Gt22vEmiw77GT+AfKPw+tcob2FUZTMhP16A1El3MXDx3yIO2yHPAP160ISujtFe1Kg7Q2e4QkUVx02o3UbgfbZn46rGgA9sEUU7jOftrBDFjLkHqBWjHp6FFTgKD3JyaKKdkS5Mt3Ftb/Zi0W1ZAOGI4z71HYQtdWjrJIrZGflUjHNFFK2oJuxeXTEKglMn1HeiiinZBdn/9k='],
    // the owner's photo for the Art Map (12 Nov 2026): a bright corridor with colourful paintings on wooden easels and benches
    'easels-corridor' => [[640, 1280, 1920, 2000], 2000, 1331,
        'A bright corridor with colourful paintings on wooden easels, benches and tall windows',
        'ممر مضيء فيه لوحات ملوّنة على حوامل خشبية ومقاعد ونوافذ طويلة',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACQDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwCKDVbaOUIDIy9CSmMGt2zmiuYlkhYMh6MK49ojFNJMYxsY8HJP1HtV/TLo7fLjMqo2fmBwRz2/nXO7nQrM67hF3MPlHemz3EKWqTCaFEkUMjSPgMCcZA6ms6509Fha6Nw06mNsZk6cdx2xUmsW8F5BYvFIixQJs2qRmmmDjoTywPO5ayvxNGOCyKCM9xRU2jW0VpZlBcK4Zy30yBxRV8yMbHDS36z2VnZhOUA3nOATjFTWjKr5UYHaiiiS1LizqNIvFdDZ3Co8MvGG6A1Z1jRV+zia2SSMxjP7sBz+WRmiipBuxzceralbr5dsYJkH8ZhOc+/NFFFGxJ//2Q=='],
    // the owner's photo for the Gallery (8 Nov 2026): a quiet gallery hall with framed photographs and a bench, in black and white
    'gallery-hall' => [[640, 1280, 1920, 2000], 2000, 1333,
        'A quiet gallery hall with framed photographs on white walls and a bench in the middle, in black and white',
        'قاعة عرض هادئة بصور مؤطرة على جدران بيضاء ومقعد في الوسط، بالأبيض والأسود',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDABALDA4MChAODQ4SERATGCgaGBYWGDEjJR0oOjM9PDkzODdASFxOQERXRTc4UG1RV19iZ2hnPk1xeXBkeFxlZ2P/2wBDARESEhgVGC8aGi9jQjhCY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2P/wAARCAAYACQDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDop44preIRuv8AtYwahtrKBTvfacdj0rLivbaEZQDfjnaM1HNqltFA5ZVCdT8pA/nQB0RhtJl2+Wi/7oGari0iRt0bYweua56z1u1aU+QoyBklR0/WraanEEKhMA9Qq4/rQBsXk1ssoCyJ93saKwnuYGbK7MUUAYq3PyDBqK4Yywlcg5IP60UUARWaGFyxxyCOB75q359FFAEElyS3JooooA//2Q=='],
    'open-books' => [[640, 1280, 1920, 2000], 2000, 1253,
        'A stack of open books seen from the side, in black and white',
        'كومة من الكتب المفتوحة من الجانب، بالأبيض والأسود',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAXACQDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDBU63kFLq4B92UD/0Krf8ApuBJPqN4hIwYkeNsn1BZjgdOKj1OTwvHeC1Fv5gOA1xbyMQpPf3xU0GgwWkhe9vYIBHIRbPbR+bLJj+LC8gDPUmgCWzspL12NzFdX0CLuVbiFEQHsfMVh6HirJTUmXbZWmnkD/lnDBC+PzfNQ2mn2z7zb6/dBepxbOMe7c4z+NI2h6CHLy6ldtIerrEgOfXqTQAS2uuK+DommufUwRf0NFSrZaMgx/beqH/tsR+gNFAGbZ6ldTsrrPfzJnkCby8/ju4/KnOl2fm+ybifvGSUNuPqcg0UUAJ5rbs6i0kUESkhbTYC5PY/KMfXtVa6vbCK2FwdCi8ottV5rl3Zj+FFFAFIa1px5Gk2a+2ZP8aKKKAP/9k='],
    // the owner's photo for the 404 page (6 Nov 2026): a visitor walking alone through a dark gallery arch
    'gallery-arch' => [[640, 1280, 1920, 2000], 2000, 1999,
        'A visitor walking alone through a dark gallery arch towards framed paintings, in black and white',
        'زائر يمشي وحده عبر قوس معرض مظلم نحو لوحات مؤطرة، بالأبيض والأسود',
        'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAkACQDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDzGnbTxx1pKvWtrcyx+ZFavOqA529h9KAKJGOtJT2YMWLKck/lTaACiiigCS2TzJ1X1rtNAijtYk82FyPmP+yw2jr+PSuV0doY7wS3DAKtdpBqOnzR4Eq5Ix1xigDjdcaN9Yu2hQKhk6Dpnv8ArWfXT6lZ2rCTyWUk8nnqa52WBos7gcdjQBFRRRQAUBiOhIoooAeJ5R/Gaazsx+Yk0UUAJRRRQB//2Q=='],
];
/* The part of each photo that always stays in view when the photo fills the screen (owner,
   6 Nov 2026): "x% y%" of the photo, aimed at its main subject. A phone (tall screen) keeps the
   middle of a wide photo around this point; a computer (wide screen) keeps the band around it. */
const OW_STUDIO_FOCUS = [
    'home-painting' => '62% 42%',   // the hand with the brush and the canvas
    'brushes-jars'  => '45% 45%',   // the brushes in their jars
    'paint-cloth'   => '50% 50%',
    'pink-palette'  => '58% 30%',   // the palette (top) and the brushes
    'paint-buckets' => '42% 40%',   // the hand dipping the brush into the buckets
    'palette-hand'  => '38% 52%',   // the hand and the palette
    'gallery-visit' => '50% 58%',   // the woman and the paintings around her
    'studio-table'  => '55% 75%',   // the studio table with its brushes and jars (lower part of the photo)
    'art-book'      => '50% 45%',   // the open art book and the hand turning its page
    'easels-class'  => '42% 55%',
    'museum-walk'   => '50% 0%',    // the arch (top of the photo) and the visitors walking under it; laptops trim only the floor
    'gallery-hall'  => '50% 50%',   // the framed photographs and the bench
    'easels-corridor' => '50% 50%', // the paintings on easels down the corridor
    'events-hall'   => '50% 50%',   // the posters on the wall and the tables below them
    'open-books'    => '50% 50%',   // the open pages
    'gallery-arch'  => '40% 48%',   // the visitor in the lit arch   // the row of easels with their canvases
];
/* Where the page title card (and an empty-list card) sits on computers, so it stays away from the photo's
   main subject (owner, 6 Nov 2026): 'start' = the reading side (right in Arabic, left in English),
   'left' / 'right' = that side of the screen. Not listed = the middle */
const OW_STUDIO_SIDE = [];   // none for now: every page uses the same centred column as Art Institutes (owner, 6 Nov 2026)
/* The tiny first frames of the homepage videos (their blurred fill, index.php) */
const OW_HERO_TINY = [
    'blue' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA0JCgsKCA0LCgsODg0PEyAVExISEyccHhcgLikxMC4pLSwzOko+MzZGNywtQFdBRkxOUlNSMj5aYVpQYEpRUk//2wBDAQ4ODhMREyYVFSZPNS01T09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0//wAARCAAYACsDASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAQFBgH/xAAqEAACAgIABAUEAwEAAAAAAAABAgMRAAQFEiExEyJBUWEGgaGxMkJSwf/EABcBAQADAAAAAAAAAAAAAAAAAAQCAwX/xAAhEQEAAgIBAwUAAAAAAAAAAAABAAIDESEEgcESMVGhsf/aAAwDAQACEQMRAD8Azm3MX4gmyZD4kjHnu7u7B/GaqLk2+Hh5VpyLs/6HUEff/uQX1ojsLMjh0Leax/HNHuBORFiBaOx2F2AMQXo4tHPvKb4slMmrcSRLyzrsTT2spQEkC+ZifKOnb0GU5YJOHaEewdVN4EKXBUiiooEA9v3k7WjMwkiD8rtsleayD8X+MucK1pNOXZXbL+HNKfNI9+wUj0ArpXvhuqs1AHtL+k02VO8W4grLowbOqGSVgsnKOwPeqxeD6ijMKmaIK/qF6j7Xlrf1q12ROnlIHxmDl150kZTGRWFw5F3tjMuMtrROcO29DXtdjYZXK9f69b7XlMfUOpNJHBAXKAUGINhienX2wwx1h9SrBiaOI/peGnEVUx2Wk53PyfXFN/dk3Nbldm8NyrA3SsCW/QrDDIlS1i6cgeZKlmtLVH58Sto8RkSODX3G5hKKjaj5a6AEnvfe/nG5OHwSOXINn2OGGZduOZonH1+T/9k=',
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
    /* the photo fills the screen (cover): it is as wide as the screen, or wider on a tall screen */
    $sizes = ($class === 'ow-studio' ? '(max-width: 768px) 100vw, ' : '') . 'max(100vw, ' . round(100 * $W / $H, 1) . 'vh)';
    $focus = OW_STUDIO_FOCUS[$key] ?? '50% 50%';
    $h = '<picture class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">';
    foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $ext => $mime) $h .= '<source type="' . $mime . '" srcset="' . $set($ext) . '" sizes="' . $sizes . '">';
    $h .= '<img src="' . $b . owStudioFile($key, $widths[0], 'jpg') . '" srcset="' . $set('jpg') . '" sizes="' . $sizes . '"'
        . ' width="' . $W . '" height="' . $H . '" style="object-position:' . $focus . '" decoding="' . ($eager ? 'sync' : 'async') . '"'
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
/* Phones (owner, 7 Nov 2026): the whole photo sits at the top of the page at full width and fades into this colour,
   the average of the photo's own bottom edge, which then fills the rest of the page (oweili.css, 768 px and narrower) */
const OW_STUDIO_UNDER = [
    'art-book' => '#2a211b', 'brushes-jars' => '#b7ad90', 'easels-class' => '#7c9ca7', 'gallery-arch' => '#020202',
    'gallery-visit' => '#895233', 'home-painting' => '#091b1c', 'museum-walk' => '#633f1b', 'events-hall' => '#4b6b6c', 'gallery-hall' => '#404040', 'easels-corridor' => '#8b7f73', 'open-books' => '#4f4f4f',
    'paint-buckets' => '#787673', 'paint-cloth' => '#9a838f', 'palette-hand' => '#2a2629', 'pink-palette' => '#827d73',
    'studio-table' => '#8c8572',
];
function owStudioBackdrop(string ...$keys): string {
    foreach ($keys as $k) {
        if (!owStudioHas($k)) continue;
        /* the tiny copy sits behind the photo only until it has loaded (no white flash), never as a frame */
        $t = OW_STUDIO[$k][5] ?? '';
        $vars = '--ow-pos:' . (OW_STUDIO_FOCUS[$k] ?? '50% 50%')
              . ($t !== '' ? ';--ow-tiny:url(' . $t . ')' : '');
        $side = OW_STUDIO_SIDE[$k] ?? '';
        $under = OW_STUDIO_UNDER[$k] ?? '';
        $ratio = round(OW_STUDIO[$k][2] / OW_STUDIO[$k][1], 4);   // the photo's height for its width (phones keep the page below it)
        return '<style>html body{background:transparent!important}:root{' . ($under !== '' ? '--ow-under:' . $under . ';' : '') . '--ow-ratio:' . $ratio . '}</style><div class="ow-backdrop" style="' . $vars . '"'
             . ($side !== '' ? ' data-side="' . $side . '"' : '') . '>'
             . owStudioPhoto($k, true, 'ow-studio') . '</div>';
    }
    return '';
}
