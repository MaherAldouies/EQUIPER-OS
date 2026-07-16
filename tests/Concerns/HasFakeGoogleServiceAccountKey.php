<?php

namespace Tests\Concerns;

/**
 * A throwaway RSA private key used only to exercise
 * GoogleServiceAccountToken's JWT-signing code path in tests — never
 * used against the real Google OAuth endpoint (Http::fake intercepts
 * that call). Generated once via `openssl genrsa`; not a real
 * credential for anything.
 */
trait HasFakeGoogleServiceAccountKey
{
    private function fakePrivateKeyPem(): string
    {
        return <<<'PEM'
        -----BEGIN PRIVATE KEY-----
        MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCzY/rOajh7uUPZ
        U2MnBUEuQ5zo0uDfVxDLmUYZSNcKGMfUDib33dmXpEwmcclBA0vSBapNgjHBhJ1I
        DOkBSlT7ggQNsJZLSSpVC0KCx12aHMIjhuvwboPAFaRLv69Fx8vL0VoQoXOdR76b
        cnIO0dTHZGVuVH9eaQFBU6Sb5176iiX2hbVjSIF+84GL6lEM9XWQyKoalY1OgBoZ
        N6bbQ4c8uLO59wIcKjW4HkmwFcRuZcMmueQaQHQifNxBWs9gIeEEOuCYKl+Qua13
        DFokF3OWNWx6l8pCSB+H+B3kjtbTYv2BBvuY6zHA0laCJN1LKQhKaYHtWiK7wjUp
        qMkQBUh/AgMBAAECggEATxMktMVUxZDqTAt6WvJxg2uxaF3AWyKdWCnloPKfO6el
        uh9M5RjJOBNnr9Cbdol17AusQMNBnHyQ+fga44M5B66Ni91JusDzJI60kEJCS9Me
        bpukOBT3BX5ksDE8GQXTmzHawhbxCCTea9YcWdT7trLWFTaw+FRQBdRRbJmmH8hz
        RPEZqinr0hgAIewrM9+IHSIiqCxbLrLmgs/LkciAjzWeBu144FkpfSjp1jC5dV0L
        kG1iqiltErapcH8DMzZkGJp8ON8a8olqHu323dMmsPxHt7opIVt9W7mBYg9PfGUk
        0t9nFgAuWS2gG7nSBrfzEIFyymNO6QkYjcdW6MOahQKBgQD7zApNlA60nfAEo6cS
        u/o+bGbnxQVMOGBQurtooyb443+tyD24SItJQE3l0jNhgnxjmCty2zViP5N1o5C8
        fSkoZpXM7joWHTWjytM1VIG6l7VST5Oe0+iQ35oAIipdj6byRGRDmsSODZjbe8to
        1fMl40z/FRB93SE8L3+m+q2HJQKBgQC2Yomhx7S2LgA4a67M+bEgrAMDGxSzrRBl
        fw7dWgmrN8pjRBWDbdObkk+t0h6ztCCgWD1EzfxDrueuKfswEgs8H3Mdx9pI945C
        IJgAM2gmwyUwTMoFTb993On/Hp0xPDbbDSuqyi3rr0E22VU1O+gjeEtEW7x3YBIr
        q+HVwuzB0wKBgB+PBUOP6P/uQ6TxNgF9GfI09/jYkr9o2XmEzfRhboMHUHthe4rb
        XMnDi3/ghpwUFK8O5XMx0lEMEtlNtNhK/5uWr/PNb+5RnuV2iF5IxzGxzgIRAzmG
        6cEupUia/6BWXuBCfiaTAUuknKH1tBrhpeX7xSy7YSPFUWMPuPoetR0ZAoGBAIf6
        w56o5KUHewaV0ofkihlP5hcEs9SabaUerBiArsDHIdAMzPVmhuRwm5N/G4DkBYdH
        Arcv1Ksp1nZVun/GlDXJROypOqg4WgoXfvx3V2m/CRUPy3dU1jai5JtfWdeBi2ya
        TzpQ7xfPXJHmS88a0SLQ510aAFQIfsOsrP3RDPAdAoGAMSI+cH4Y8aw0aM8l/yiI
        a08XVArDUbAANK2XRHoHkMtSViczmYCN09FOcYIybykT7MjLNwngS2kVlytTwB1B
        B1pWFqgOyJ0bMO0qqzZZ+7y/jONYEpfy/n6DXEvctZpwMF3ofW3DOxvig8IUlejK
        e04PeNWeeA3uRngfnIaVpSE=
        -----END PRIVATE KEY-----
        PEM;
    }
}
