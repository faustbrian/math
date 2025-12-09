# TODO

## High-Value Improvements

**1. Missing Modern PHP Features**
- No `readonly` keyword on the class level (PHP 8.2+) — only properties are readonly
- Missing `WeakMap` caching for frequently used values (zero, one, ten) instead of static variables
- No union type refinements available in PHP 8.1+

**2. API Enhancements**
- Missing `isOne()` method (common check, have `isZero()` but not `isOne()`)
- No `BigDecimal::round()` convenience method — must use `dividedBy(1, scale, mode)`
- Missing `BigRational::clamp()` unlike BigInteger/BigDecimal
- No `BigInteger::nthRoot(n)` — only `sqrt()` exists
- Missing `BigInteger::isPrime()` / `nextPrime()` for cryptographic use cases

**3. Performance Opportunities**
- `BigRational::plus/minus` doesn't simplify automatically — denominators grow unbounded
- No Karatsuba multiplication in NativeCalculator for very large numbers
- Calculator selection happens via `CalculatorRegistry::get()` on every operation — could be resolved once

**4. Type Safety**
- `@phpstan-ignore` comments scattered (e.g., `BigDecimal:764`, `BigDecimal:791`)
- `numeric-string` return types could be enforced more strictly
- Missing generic type parameters for static analysis

**5. DX Improvements**
- No `BigDecimal::fromString()` that accepts locale-aware formats (e.g., `1.234,56`)
- Missing `BigNumber::equals()` as alias for `isEqualTo()` (more intuitive)
- No `BigInteger::factorial()` built-in

**6. Consistency Issues**
- `BigInteger::gcdMultiple()` is static but `gcd()` is instance — inconsistent API
- `BigRational` lacks `ten()` equivalent to other types (it exists but returns `10/1`)

**7. New Static Methods ([#97](https://github.com/brick/math/issues/97) — partial)**
- Add `BigNumber::minimumOf(...$values)` — returns minimum, preserving widest type
- Add `BigNumber::maximumOf(...$values)` — returns maximum, preserving widest type
- Add `BigNumber::widen(BigNumber $a, BigNumber $b): BigNumber` — returns operands promoted to widest compatible type
  - Type hierarchy: BigInteger < BigDecimal < BigRational
  - Use PHPStan conditional return types for static analysis support
  - Note: IDE support varies (PHPStorm 2022+, Intelephense spotty)
- **NOT implementing**: `plusAuto`, `minusAuto`, `dividedByAuto` etc. — pollutes API with duplicate methods, encourages lazy typing

## GitHub Issues to Address

**8. Implement rounding mode in sqrt() ([#99](https://github.com/brick/math/issues/99))**
- Add rounding mode support to `BigInteger::sqrt()`
- Add rounding mode support to `BigDecimal::sqrt()`
- Related to issue #98 which documented "BigDecimal::sqrt() incorrect rounding with HALF_UP"

**9. Output a BigRational in decimal form with period ([#74](https://github.com/brick/math/issues/74))** ✅
- Add functionality to display `BigRational` values as decimal numbers with repeating cycle notation
- Examples:
  - `10/3` → `3.(3)` (where the 3 repeats infinitely)
  - `171/70` → `2.4(428571)` (where 428571 is the repeating sequence)
- Need to determine standard notation for representing repeating decimals

## Additional Features (Parity with Other Languages)

Based on analysis of Python mpmath, Rust malachite/rug, Haskell, and GMP:

**10. Logarithms & Exponentials** (High Priority)
- `BigDecimal::ln(int $scale)` — natural logarithm
- `BigDecimal::log10(int $scale)` — base-10 logarithm
- `BigDecimal::log(BigNumber $base, int $scale)` — logarithm with arbitrary base
- `BigDecimal::exp(int $scale)` — natural exponential (e^x)

**11. Mathematical Constants** (Medium Priority)
- `BigDecimal::pi(int $scale)` — π to arbitrary precision (Chudnovsky algorithm)
- `BigDecimal::e(int $scale)` — Euler's number to arbitrary precision
- `BigDecimal::phi(int $scale)` — golden ratio (1 + √5) / 2

**12. Trigonometric Functions** (Medium Priority)
- `BigDecimal::sin(int $scale)`, `cos(int $scale)`, `tan(int $scale)`
- `BigDecimal::asin(int $scale)`, `acos(int $scale)`, `atan(int $scale)`
- `BigDecimal::sinh(int $scale)`, `cosh(int $scale)`, `tanh(int $scale)` — hyperbolic

**13. Factorial & Combinatorics** (Medium Priority)
- `BigInteger::factorial()` — n!
- `BigInteger::binomial(int $k)` — n choose k (binomial coefficient)
- `BigInteger::permutations(int $k)` — n! / (n-k)!
- `BigInteger::doubleFactorial()` — n!! (product of all integers from 1 to n with same parity)

**14. Additional Number Theory** (Medium Priority)
- `BigInteger::lcm(BigInteger $that)` — least common multiple
- `BigInteger::modPow(BigInteger $exp, BigInteger $mod)` — modular exponentiation
- `BigInteger::modInverse(BigInteger $mod)` — modular multiplicative inverse
- `BigInteger::jacobi(BigInteger $n)` — Jacobi symbol
- `BigInteger::legendre(BigInteger $p)` — Legendre symbol

**15. Bit Operations on BigInteger** (Medium Priority)
- `BigInteger::and(BigInteger $that)` — bitwise AND
- `BigInteger::or(BigInteger $that)` — bitwise OR
- `BigInteger::xor(BigInteger $that)` — bitwise XOR
- `BigInteger::not()` — bitwise NOT (one's complement)
- `BigInteger::shiftLeft(int $n)` — left shift by n bits
- `BigInteger::shiftRight(int $n)` — right shift by n bits
- `BigInteger::bitLength()` — number of bits needed to represent (excluding sign)
- `BigInteger::bitCount()` — number of set bits (popcount)
- `BigInteger::testBit(int $n)` — test if bit n is set
- `BigInteger::setBit(int $n)` — return copy with bit n set
- `BigInteger::clearBit(int $n)` — return copy with bit n cleared
- `BigInteger::flipBit(int $n)` — return copy with bit n flipped

**16. Random BigInteger Generation** (Lower Priority)
- `BigInteger::randomBits(int $numBits)` — random number with exactly n bits
- `BigInteger::randomRange(BigInteger $min, BigInteger $max)` — random in range [min, max]
- `BigInteger::randomPrime(int $numBits, int $certainty)` — random probable prime

**17. Binary/Base Conversion** (Lower Priority)
- `BigInteger::fromBytes(string $bytes, bool $signed = false)` — from binary representation
- `BigInteger::toBytes(bool $signed = false)` — to binary representation
- `BigInteger::fromBase(string $value, int $base)` — from arbitrary base (2-36)
- `BigInteger::toBase(int $base)` — to arbitrary base (2-36)
